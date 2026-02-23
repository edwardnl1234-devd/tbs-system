<?php

namespace App\Http\Controllers;

use App\Http\Requests\Weighing\StoreWeighingRequest;
use App\Http\Requests\Weighing\UpdateWeighingRequest;
use App\Http\Requests\Weighing\WeighInRequest;
use App\Http\Requests\Weighing\WeighOutRequest;
use App\Http\Resources\WeighingResource;
use App\Models\Queue;
use App\Models\StockTbs;
use App\Models\TbsPrice;
use App\Models\Weighing;
use App\Traits\ApiResponse;
use App\Traits\GeneratesTicketNumber;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WeighingController extends Controller
{
    use ApiResponse, GeneratesTicketNumber;

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

            // Check if queue exists
            $queue = Queue::with('supplier')->find($request->queue_id);
            if (!$queue) {
                return $this->notFound('Queue not found');
            }

            // Prevent double processing - check if weighing already exists for this queue
            $existingWeighing = Weighing::where('queue_id', $request->queue_id)->first();
            if ($existingWeighing) {
                return $this->error('Antrian ini sudah ditimbang (Tiket: ' . $existingWeighing->ticket_number . ')', 400);
            }

            // Check queue status - must be waiting or processing
            if (!in_array($queue->status, ['waiting', 'processing'])) {
                return $this->error('Antrian tidak dalam status yang valid untuk ditimbang', 400);
            }

            // Update queue status to processing
            $queue->update(['status' => 'processing', 'call_time' => now()]);

            // Get company name from supplier
            $companyName = $queue->supplier?->name ?? 'Unknown';
            
            // Get product type from request (default TBS)
            $productType = $request->product_type ?? 'TBS';
            
            // Extract company code and product code for ticket number
            $companyCode = $this->extractCompanyCode($companyName);
            $productCode = $this->getProductTypeCode($productType);
            $year = now()->format('y');

            // Generate ticket number: NNNN/XX/P/YY
            // Find the highest sequence number for this company code + product code + year combination
            // to avoid duplicate ticket numbers (unique constraint is global, not per day)
            $maxSequence = Weighing::where('ticket_number', 'like', "%/{$companyCode}/{$productCode}/{$year}")
                ->get()
                ->map(function ($weighing) {
                    $ticket = $weighing->ticket_number;
                    if (str_contains($ticket, '/')) {
                        return (int) explode('/', $ticket)[0];
                    }
                    return 0;
                })
                ->max() ?? 0;

            $sequence = $maxSequence + 1;
            $ticketNumber = $this->generateTicketNumber($sequence, $companyName, $productType);

            // Netto SELALU dihitung otomatis dari bruto - tara
            $nettoWeight = null;
            $status = 'weigh_in';
            $weighOutTime = null;
            
            if ($request->bruto_weight && $request->tara_weight) {
                // Hitung netto otomatis
                $nettoWeight = max(0, $request->bruto_weight - $request->tara_weight);
                $status = 'completed';
                $weighOutTime = now();
            }

            $weighing = Weighing::create([
                'queue_id' => $request->queue_id,
                'operator_id' => auth()->id(),
                'ticket_number' => $ticketNumber,
                'product_type' => $productType,
                'bruto_weight' => $request->bruto_weight,
                'tara_weight' => $request->tara_weight,
                'netto_weight' => $nettoWeight,
                // price_per_kg akan di-set otomatis oleh WeighingObserver jika tidak disediakan
                'price_per_kg' => $request->price_per_kg,
                'weigh_in_time' => now(),
                'weigh_out_time' => $weighOutTime,
                'status' => $status,
                'notes' => $request->notes,
            ]);

            // Hitung total_price setelah create (karena price mungkin di-set oleh observer)
            if ($weighing->netto_weight && $weighing->price_per_kg) {
                $weighing->total_price = $weighing->netto_weight * $weighing->price_per_kg;
                $weighing->saveQuietly(); // saveQuietly agar tidak trigger observer lagi
            }

            // If completed, update queue status
            if ($status === 'completed') {
                $queue->update(['status' => 'completed']);
            }

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

            // Selalu hitung ulang netto otomatis dari bruto - tara
            if ($weighing->bruto_weight && $weighing->tara_weight) {
                $weighing->netto_weight = max(0, $weighing->bruto_weight - $weighing->tara_weight);
                if ($weighing->price_per_kg) {
                    $weighing->total_price = $weighing->netto_weight * $weighing->price_per_kg;
                }
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

            // Hitung netto otomatis dari bruto - tara
            $nettoWeight = max(0, $weighing->bruto_weight - $request->tara_weight);
            $totalPrice = $weighing->price_per_kg ? $nettoWeight * $weighing->price_per_kg : 0;

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

    /**
     * Update derivative weights (CPO, Kernel, Cangkang, Fiber, Jangkos)
     */
    public function updateDerivatives(Request $request, int $id): JsonResponse
    {
        try {
            $weighing = Weighing::find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            $validated = $request->validate([
                'cpo_weight' => 'nullable|numeric|min:0',
                'kernel_weight' => 'nullable|numeric|min:0',
                'cangkang_weight' => 'nullable|numeric|min:0',
                'fiber_weight' => 'nullable|numeric|min:0',
                'jangkos_weight' => 'nullable|numeric|min:0',
            ]);

            $weighing->update($validated);

            return $this->success(
                new WeighingResource($weighing->load(['queue', 'operator'])),
                'Berat turunan berhasil diperbarui'
            );
        } catch (\Exception $e) {
            return $this->serverError('Gagal memperbarui berat turunan: ' . $e->getMessage());
        }
    }

    /**
     * Refresh price for a weighing based on current daily price
     */
    public function refreshPrice(int $id): JsonResponse
    {
        try {
            $weighing = Weighing::with('queue')->find($id);

            if (!$weighing) {
                return $this->notFound('Weighing not found');
            }

            if ($weighing->status === 'completed') {
                return $this->error('Tidak dapat mengubah harga untuk penimbangan yang sudah selesai', 400);
            }

            $supplierType = $weighing->queue?->supplier_type ?? 'umum';
            $newPrice = TbsPrice::getPriceForDate($supplierType);

            if (!$newPrice) {
                return $this->error('Harga untuk tipe supplier "' . $supplierType . '" belum diatur', 404);
            }

            $oldPrice = $weighing->price_per_kg;
            $weighing->price_per_kg = $newPrice;

            // Recalculate total if netto exists
            if ($weighing->netto_weight) {
                $weighing->total_price = $weighing->netto_weight * $newPrice;
            }

            $weighing->save();

            return $this->success(
                new WeighingResource($weighing->load(['queue', 'operator'])),
                'Harga berhasil diperbarui dari Rp ' . number_format($oldPrice ?? 0) . ' ke Rp ' . number_format($newPrice)
            );
        } catch (\Exception $e) {
            return $this->serverError('Gagal memperbarui harga: ' . $e->getMessage());
        }
    }

    /**
     * Bulk refresh prices for all pending weighings
     */
    public function bulkRefreshPrices(): JsonResponse
    {
        try {
            DB::beginTransaction();

            $weighings = Weighing::with('queue')
                ->where('status', '!=', 'completed')
                ->get();

            $updated = 0;
            $failed = 0;
            $errors = [];

            foreach ($weighings as $weighing) {
                $supplierType = $weighing->queue?->supplier_type ?? 'umum';
                $newPrice = TbsPrice::getPriceForDate($supplierType);

                if ($newPrice) {
                    $weighing->price_per_kg = $newPrice;
                    if ($weighing->netto_weight) {
                        $weighing->total_price = $weighing->netto_weight * $newPrice;
                    }
                    $weighing->saveQuietly();
                    $updated++;
                } else {
                    $failed++;
                    $errors[] = "Tiket {$weighing->ticket_number}: Harga tidak ditemukan untuk tipe '{$supplierType}'";
                }
            }

            DB::commit();

            return $this->success([
                'total_processed' => $weighings->count(),
                'updated' => $updated,
                'failed' => $failed,
                'errors' => $errors,
            ], "Berhasil memperbarui harga untuk {$updated} penimbangan");
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Gagal memperbarui harga: ' . $e->getMessage());
        }
    }
}
