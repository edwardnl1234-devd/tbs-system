<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreStockOpnameRequest;
use App\Http\Requests\Stock\UpdateStockOpnameRequest;
use App\Http\Resources\StockOpnameResource;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockOpname;
use App\Models\StockShell;
use App\Models\StockTbs;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = StockOpname::with(['countedBy', 'verifiedBy']);

        if ($request->has('product_type')) {
            $query->where('product_type', $request->product_type);
        }

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('opname_date', $request->date);
        }

        $opnames = $query->orderBy('opname_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($opnames);
    }

    public function store(StoreStockOpnameRequest $request): JsonResponse
    {
        try {
            // Get system quantity based on product type
            $systemQuantity = $this->getSystemQuantity($request->product_type);

            $variance = $request->physical_quantity - $systemQuantity;
            $variancePercentage = $systemQuantity > 0 
                ? ($variance / $systemQuantity) * 100 
                : 0;

            $opname = StockOpname::create([
                'opname_date' => $request->opname_date,
                'product_type' => $request->product_type,
                'location' => $request->location,
                'physical_quantity' => $request->physical_quantity,
                'system_quantity' => $systemQuantity,
                'variance' => $variance,
                'variance_percentage' => round($variancePercentage, 2),
                'counted_by' => auth()->id(),
                'remarks' => $request->remarks,
                'status' => 'draft',
            ]);

            return $this->created(
                new StockOpnameResource($opname->load(['countedBy'])),
                'Stock opname created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create stock opname: ' . $e->getMessage());
        }
    }

    private function getSystemQuantity(string $productType): float
    {
        return match ($productType) {
            'CPO' => StockCpo::where('status', 'available')
                ->where('movement_type', 'in')
                ->sum('quantity') - StockCpo::where('movement_type', 'out')->sum('quantity'),
            'Kernel' => StockKernel::where('status', 'available')->sum('quantity'),
            'Shell' => StockShell::where('status', 'available')->sum('quantity'),
            'TBS' => StockTbs::whereIn('status', ['ready', 'processing'])->sum('quantity'),
            default => 0,
        };
    }

    public function show(int $id): JsonResponse
    {
        $opname = StockOpname::with(['countedBy', 'verifiedBy'])->find($id);

        if (!$opname) {
            return $this->notFound('Stock opname not found');
        }

        return $this->success(new StockOpnameResource($opname));
    }

    public function update(UpdateStockOpnameRequest $request, int $id): JsonResponse
    {
        try {
            $opname = StockOpname::find($id);

            if (!$opname) {
                return $this->notFound('Stock opname not found');
            }

            if ($opname->status === 'approved') {
                return $this->error('Cannot update an approved stock opname', 400);
            }

            $opname->update($request->validated());

            // Recalculate variance
            if ($request->has('physical_quantity')) {
                $opname->variance = $opname->physical_quantity - $opname->system_quantity;
                $opname->variance_percentage = $opname->system_quantity > 0
                    ? ($opname->variance / $opname->system_quantity) * 100
                    : 0;
                $opname->save();
            }

            return $this->success(
                new StockOpnameResource($opname->load(['countedBy', 'verifiedBy'])),
                'Stock opname updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update stock opname: ' . $e->getMessage());
        }
    }

    public function verify(int $id): JsonResponse
    {
        try {
            $opname = StockOpname::find($id);

            if (!$opname) {
                return $this->notFound('Stock opname not found');
            }

            if ($opname->status !== 'draft') {
                return $this->error('Only draft opnames can be verified', 400);
            }

            $opname->update([
                'verified_by' => auth()->id(),
                'status' => 'verified',
            ]);

            return $this->success(
                new StockOpnameResource($opname->load(['countedBy', 'verifiedBy'])),
                'Stock opname verified successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to verify stock opname: ' . $e->getMessage());
        }
    }

    public function latest(): JsonResponse
    {
        $latestOpnames = StockOpname::with(['countedBy', 'verifiedBy'])
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('stock_opnames')
                    ->groupBy('product_type');
            })
            ->get();

        return $this->success(StockOpnameResource::collection($latestOpnames));
    }

    public function byDate(string $date): JsonResponse
    {
        $opnames = StockOpname::with(['countedBy', 'verifiedBy'])
            ->whereDate('opname_date', $date)
            ->get();

        return $this->success(StockOpnameResource::collection($opnames));
    }
}
