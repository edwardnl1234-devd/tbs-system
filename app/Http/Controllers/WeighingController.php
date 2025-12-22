<?php

namespace App\Http\Controllers;

use App\Http\Requests\Weighing\StoreWeighingRequest;
use App\Http\Requests\Weighing\UpdateWeighingRequest;
use App\Http\Requests\Weighing\WeighInRequest;
use App\Http\Requests\Weighing\WeighOutRequest;
use App\Http\Resources\WeighingResource;
use App\Models\Queue;
use App\Models\StockTbs;
use App\Models\Weighing;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeighingController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Weighing::with(['queue', 'queue.supplier', 'operator']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('weigh_in_time', $request->date);
        }

        if ($request->has('ticket_number')) {
            $query->where('ticket_number', 'like', '%' . $request->ticket_number . '%');
        }

        $weighings = $query->orderBy('weigh_in_time', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($weighings);
    }

    public function store(StoreWeighingRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Update queue status to processing
            $queue = Queue::find($request->queue_id);
            if ($queue) {
                $queue->update(['status' => 'processing', 'call_time' => now()]);
            }

            // Generate ticket number: TYYYYMMDDnnn
            $today = now()->format('Ymd');
            $lastWeighing = Weighing::whereDate('weigh_in_time', today())
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastWeighing ? (int) substr($lastWeighing->ticket_number, -3) + 1 : 1;
            $ticketNumber = 'T' . $today . str_pad($sequence, 3, '0', STR_PAD_LEFT);

            $weighing = Weighing::create([
                'queue_id' => $request->queue_id,
                'operator_id' => auth()->id(),
                'ticket_number' => $ticketNumber,
                'bruto_weight' => $request->bruto_weight,
                'price_per_kg' => $request->price_per_kg,
                'weigh_in_time' => now(),
                'status' => 'weigh_in',
                'notes' => $request->notes,
            ]);

            DB::commit();

            return $this->created(
                new WeighingResource($weighing->load(['queue', 'operator'])),
                'Weighing record created successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create weighing: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $weighing = Weighing::with(['queue', 'queue.truck', 'queue.supplier', 'operator', 'sortation'])->find($id);

        if (!$weighing) {
            return $this->notFound('Weighing not found');
        }

        return $this->success(new WeighingResource($weighing));
    }

    public function update(UpdateWeighingRequest $request, int $id): JsonResponse
    {
        try {
            $weighing = Weighing::find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            $weighing->update($request->validated());

            // Recalculate netto weight and total price if applicable
            if ($weighing->tara_weight) {
                $weighing->netto_weight = $weighing->bruto_weight - $weighing->tara_weight;
                $weighing->total_price = $weighing->netto_weight * $weighing->price_per_kg;
                $weighing->save();
            }

            return $this->success(
                new WeighingResource($weighing->load(['queue', 'operator'])),
                'Weighing updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update weighing: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $weighing = Weighing::find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            if ($weighing->status === 'completed') {
                return $this->error('Cannot delete a completed weighing record', 400);
            }

            $weighing->delete();

            return $this->success(null, 'Weighing deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete weighing: ' . $e->getMessage());
        }
    }

    public function weighIn(WeighInRequest $request, int $id): JsonResponse
    {
        try {
            $weighing = Weighing::find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            $weighing->update([
                'bruto_weight' => $request->bruto_weight,
                'weigh_in_time' => now(),
                'status' => 'weigh_in',
            ]);

            return $this->success(
                new WeighingResource($weighing->load(['queue', 'operator'])),
                'Weigh-in completed successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to complete weigh-in: ' . $e->getMessage());
        }
    }

    public function weighOut(WeighOutRequest $request, int $id): JsonResponse
    {
        try {
            $weighing = Weighing::find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            if ($weighing->status !== 'weigh_in') {
                return $this->error('Weigh-in must be completed first', 400);
            }

            $nettoWeight = $weighing->bruto_weight - $request->tara_weight;
            $totalPrice = $nettoWeight * $weighing->price_per_kg;

            $weighing->update([
                'tara_weight' => $request->tara_weight,
                'netto_weight' => $nettoWeight,
                'total_price' => $totalPrice,
                'weigh_out_time' => now(),
                'status' => 'weigh_out',
            ]);

            return $this->success(
                new WeighingResource($weighing->load(['queue', 'operator'])),
                'Weigh-out completed successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to complete weigh-out: ' . $e->getMessage());
        }
    }

    public function complete(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $weighing = Weighing::find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            if (!$weighing->tara_weight || !$weighing->netto_weight) {
                return $this->error('Weigh-out must be completed before marking as complete', 400);
            }

            $weighing->update(['status' => 'completed']);

            // Update queue status to completed
            if ($weighing->queue) {
                $weighing->queue->update(['status' => 'completed']);
            }

            // Create TBS stock entry if sortation exists
            if ($weighing->sortation) {
                StockTbs::create([
                    'weighing_id' => $weighing->id,
                    'sortation_id' => $weighing->sortation->id,
                    'quantity' => $weighing->sortation->final_accepted_weight,
                    'quality_grade' => $this->determineQualityGrade($weighing->sortation),
                    'status' => 'ready',
                    'received_date' => today(),
                ]);
            }

            DB::commit();

            return $this->success(
                new WeighingResource($weighing->load(['queue', 'operator', 'sortation'])),
                'Weighing completed successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to complete weighing: ' . $e->getMessage());
        }
    }

    private function determineQualityGrade($sortation): string
    {
        $total = $sortation->good_quality_weight + $sortation->medium_quality_weight + $sortation->poor_quality_weight;
        if ($total == 0) return 'C';

        $goodPercentage = ($sortation->good_quality_weight / $total) * 100;

        if ($goodPercentage >= 70) return 'A';
        if ($goodPercentage >= 40) return 'B';
        return 'C';
    }

    public function today(): JsonResponse
    {
        $weighings = Weighing::with(['queue', 'queue.truck', 'queue.supplier', 'operator'])
            ->whereDate('weigh_in_time', today())
            ->orderBy('weigh_in_time', 'desc')
            ->get();

        return $this->success(WeighingResource::collection($weighings));
    }

    public function pending(): JsonResponse
    {
        $weighings = Weighing::with(['queue', 'queue.truck', 'queue.supplier', 'operator'])
            ->where('status', '!=', 'completed')
            ->orderBy('weigh_in_time', 'asc')
            ->get();

        return $this->success(WeighingResource::collection($weighings));
    }

    public function byQueue(int $queueId): JsonResponse
    {
        $weighing = Weighing::with(['queue', 'queue.truck', 'queue.supplier', 'operator', 'sortation'])
            ->where('queue_id', $queueId)
            ->first();

        if (!$weighing) {
            return $this->notFound('Weighing not found for this queue');
        }

        return $this->success(new WeighingResource($weighing));
    }
}
