<?php

namespace App\Http\Controllers;

use App\Http\Requests\Sortation\StoreSortationRequest;
use App\Http\Requests\Sortation\UpdateSortationRequest;
use App\Http\Resources\SortationResource;
use App\Models\Sortation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SortationController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Sortation::with(['weighing', 'weighing.truck', 'mandor']);

        if ($request->has('date')) {
            $query->whereDate('sortation_time', $request->date);
        }

        if ($request->has('mandor_id')) {
            $query->where('mandor_id', $request->mandor_id);
        }

        $sortations = $query->orderBy('sortation_time', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($sortations);
    }

    public function store(StoreSortationRequest $request): JsonResponse
    {
        try {
            $sortation = Sortation::create([
                'weighing_id' => $request->weighing_id,
                'mandor_id' => auth()->id(),
                'good_quality_weight' => $request->good_quality_weight ?? 0,
                'medium_quality_weight' => $request->medium_quality_weight ?? 0,
                'poor_quality_weight' => $request->poor_quality_weight ?? 0,
                'reject_weight' => $request->reject_weight ?? 0,
                'assistant_deduction' => $request->assistant_deduction ?? 0,
                'deduction_reason' => $request->deduction_reason,
                'final_accepted_weight' => $request->final_accepted_weight,
                'mandor_score' => $request->mandor_score,
                'operator_discipline_score' => $request->operator_discipline_score,
                'sortation_time' => now(),
                'notes' => $request->notes,
            ]);

            return $this->created(
                new SortationResource($sortation->load(['weighing', 'mandor'])),
                'Sortation record created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create sortation: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $sortation = Sortation::with(['weighing', 'weighing.truck', 'weighing.supplier', 'mandor'])->find($id);

        if (!$sortation) {
            return $this->notFound('Sortation not found');
        }

        return $this->success(new SortationResource($sortation));
    }

    public function update(UpdateSortationRequest $request, int $id): JsonResponse
    {
        try {
            $sortation = Sortation::find($id);

            if (!$sortation) {
                return $this->notFound('Sortation not found');
            }

            $sortation->update($request->validated());

            return $this->success(
                new SortationResource($sortation->load(['weighing', 'mandor'])),
                'Sortation updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update sortation: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $sortation = Sortation::find($id);

            if (!$sortation) {
                return $this->notFound('Sortation not found');
            }

            $sortation->delete();

            return $this->success(null, 'Sortation deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete sortation: ' . $e->getMessage());
        }
    }

    public function byWeighing(int $weighingId): JsonResponse
    {
        $sortation = Sortation::with(['weighing', 'mandor'])
            ->where('weighing_id', $weighingId)
            ->first();

        if (!$sortation) {
            return $this->notFound('Sortation not found for this weighing');
        }

        return $this->success(new SortationResource($sortation));
    }

    public function today(): JsonResponse
    {
        $sortations = Sortation::with(['weighing', 'weighing.truck', 'mandor'])
            ->whereDate('sortation_time', today())
            ->orderBy('sortation_time', 'desc')
            ->get();

        return $this->success(SortationResource::collection($sortations));
    }

    public function performance(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $performance = Sortation::select(
                DB::raw('DATE(sortation_time) as date'),
                DB::raw('COUNT(*) as total_sortations'),
                DB::raw('SUM(final_accepted_weight) as total_accepted_weight'),
                DB::raw('SUM(reject_weight) as total_reject_weight'),
                DB::raw('AVG(mandor_score) as avg_mandor_score'),
                DB::raw('AVG(operator_discipline_score) as avg_discipline_score')
            )
            ->whereBetween(DB::raw('DATE(sortation_time)'), [$startDate, $endDate])
            ->groupBy(DB::raw('DATE(sortation_time)'))
            ->orderBy('date', 'desc')
            ->get();

        $summary = [
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_sortations' => $performance->sum('total_sortations'),
            'total_accepted_weight' => $performance->sum('total_accepted_weight'),
            'total_reject_weight' => $performance->sum('total_reject_weight'),
            'average_mandor_score' => $performance->avg('avg_mandor_score'),
            'average_discipline_score' => $performance->avg('avg_discipline_score'),
            'daily_breakdown' => $performance,
        ];

        return $this->success($summary);
    }
}
