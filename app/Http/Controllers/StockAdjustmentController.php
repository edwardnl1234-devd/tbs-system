<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreStockAdjustmentRequest;
use App\Http\Resources\StockAdjustmentResource;
use App\Models\StockAdjustment;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = StockAdjustment::with(['adjustedBy', 'approvedBy']);

        if ($request->has('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('adjustment_date', $request->date);
        }

        $adjustments = $query->orderBy('adjustment_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($adjustments);
    }

    public function store(StoreStockAdjustmentRequest $request): JsonResponse
    {
        try {
            $difference = $request->physical_stock - $request->system_stock;

            $adjustment = StockAdjustment::create([
                'product_type' => $request->product_type,
                'system_stock' => $request->system_stock,
                'physical_stock' => $request->physical_stock,
                'difference' => $difference,
                'adjustment_type' => $request->adjustment_type,
                'reason' => $request->reason,
                'adjusted_by' => auth()->id(),
                'adjustment_date' => $request->adjustment_date,
                'status' => 'pending',
            ]);

            return $this->created(
                new StockAdjustmentResource($adjustment->load('adjustedBy')),
                'Stock adjustment created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create stock adjustment: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $adjustment = StockAdjustment::with(['adjustedBy', 'approvedBy'])->find($id);

        if (!$adjustment) {
            return $this->notFound('Stock adjustment not found');
        }

        return $this->success(new StockAdjustmentResource($adjustment));
    }

    public function approve(int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $adjustment = StockAdjustment::find($id);

            if (!$adjustment) {
                return $this->notFound('Stock adjustment not found');
            }

            if ($adjustment->status !== 'pending') {
                return $this->error('Only pending adjustments can be approved', 400);
            }

            // Apply the adjustment to stock
            $this->applyAdjustment($adjustment);

            $adjustment->update([
                'approved_by' => auth()->id(),
                'status' => 'approved',
            ]);

            DB::commit();

            return $this->success(
                new StockAdjustmentResource($adjustment->load(['adjustedBy', 'approvedBy'])),
                'Stock adjustment approved successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to approve stock adjustment: ' . $e->getMessage());
        }
    }

    private function applyAdjustment(StockAdjustment $adjustment): void
    {
        $quantity = abs($adjustment->difference);
        $movementType = $adjustment->difference >= 0 ? 'in' : 'out';

        switch ($adjustment->product_type) {
            case 'CPO':
                StockCpo::create([
                    'quantity' => $quantity,
                    'stock_type' => 'persediaan',
                    'movement_type' => $movementType === 'in' ? 'adjustment' : 'out',
                    'reference_number' => 'ADJ-' . $adjustment->id,
                    'stock_date' => $adjustment->adjustment_date,
                    'status' => 'available',
                    'notes' => 'Stock adjustment: ' . $adjustment->reason,
                ]);
                break;

            case 'Kernel':
                if ($movementType === 'in') {
                    StockKernel::create([
                        'quantity' => $quantity,
                        'status' => 'available',
                        'stock_date' => $adjustment->adjustment_date,
                    ]);
                }
                break;

            case 'Shell':
                if ($movementType === 'in') {
                    StockShell::create([
                        'quantity' => $quantity,
                        'status' => 'available',
                        'stock_date' => $adjustment->adjustment_date,
                    ]);
                }
                break;
        }
    }

    public function reject(int $id): JsonResponse
    {
        try {
            $adjustment = StockAdjustment::find($id);

            if (!$adjustment) {
                return $this->notFound('Stock adjustment not found');
            }

            if ($adjustment->status !== 'pending') {
                return $this->error('Only pending adjustments can be rejected', 400);
            }

            $adjustment->update([
                'approved_by' => auth()->id(),
                'status' => 'rejected',
            ]);

            return $this->success(
                new StockAdjustmentResource($adjustment->load(['adjustedBy', 'approvedBy'])),
                'Stock adjustment rejected'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to reject stock adjustment: ' . $e->getMessage());
        }
    }

    public function pending(): JsonResponse
    {
        $adjustments = StockAdjustment::with(['adjustedBy'])
            ->where('status', 'pending')
            ->orderBy('adjustment_date', 'desc')
            ->get();

        return $this->success(StockAdjustmentResource::collection($adjustments));
    }
}
