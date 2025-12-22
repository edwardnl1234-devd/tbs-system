<?php

namespace App\Http\Controllers;

use App\Http\Requests\Stock\StoreStockCpoRequest;
use App\Http\Requests\Stock\UpdateStockCpoRequest;
use App\Http\Requests\Stock\StoreStockKernelRequest;
use App\Http\Requests\Stock\StoreStockShellRequest;
use App\Http\Resources\StockCpoResource;
use App\Http\Resources\StockKernelResource;
use App\Http\Resources\StockShellResource;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    use ApiResponse;

    // ======================= CPO STOCK =======================

    public function indexCpo(Request $request): JsonResponse
    {
        $query = StockCpo::with('production');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('tank_number')) {
            $query->where('tank_number', $request->tank_number);
        }

        if ($request->has('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->has('date_from')) {
            $query->whereDate('stock_date', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('stock_date', '<=', $request->date_to);
        }

        $stocks = $query->orderBy('stock_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($stocks);
    }

    public function storeCpo(StoreStockCpoRequest $request): JsonResponse
    {
        try {
            $stock = StockCpo::create($request->validated());

            return $this->created(
                new StockCpoResource($stock),
                'CPO stock created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create CPO stock: ' . $e->getMessage());
        }
    }

    public function showCpo(int $id): JsonResponse
    {
        $stock = StockCpo::with('production')->find($id);

        if (!$stock) {
            return $this->notFound('CPO stock not found');
        }

        return $this->success(new StockCpoResource($stock));
    }

    public function updateCpo(UpdateStockCpoRequest $request, int $id): JsonResponse
    {
        try {
            $stock = StockCpo::find($id);

            if (!$stock) {
                return $this->notFound('CPO stock not found');
            }

            $stock->update($request->validated());

            return $this->success(
                new StockCpoResource($stock),
                'CPO stock updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update CPO stock: ' . $e->getMessage());
        }
    }

    public function destroyCpo(int $id): JsonResponse
    {
        try {
            $stock = StockCpo::find($id);

            if (!$stock) {
                return $this->notFound('CPO stock not found');
            }

            if ($stock->status === 'sold') {
                return $this->error('Cannot delete sold stock', 400);
            }

            $stock->delete();

            return $this->success(null, 'CPO stock deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete CPO stock: ' . $e->getMessage());
        }
    }

    public function summaryCpo(): JsonResponse
    {
        $summary = [
            'total_in' => StockCpo::where('movement_type', 'in')
                ->where('status', 'available')
                ->sum('quantity'),
            'total_out' => StockCpo::where('movement_type', 'out')
                ->sum('quantity'),
            'total_available' => StockCpo::where('status', 'available')
                ->where('movement_type', 'in')
                ->sum('quantity') - StockCpo::where('movement_type', 'out')->sum('quantity'),
            'total_reserved' => StockCpo::where('status', 'reserved')
                ->sum('quantity'),
            'by_quality' => StockCpo::where('status', 'available')
                ->where('movement_type', 'in')
                ->select('quality_grade', DB::raw('SUM(quantity) as total'))
                ->groupBy('quality_grade')
                ->get(),
            'by_tank' => StockCpo::where('status', 'available')
                ->where('movement_type', 'in')
                ->whereNotNull('tank_number')
                ->select('tank_number', DB::raw('SUM(quantity) as total'))
                ->groupBy('tank_number')
                ->get(),
        ];

        return $this->success($summary);
    }

    public function byTankCpo(string $tank): JsonResponse
    {
        $stocks = StockCpo::where('tank_number', $tank)
            ->where('status', 'available')
            ->orderBy('stock_date', 'desc')
            ->get();

        return $this->success(StockCpoResource::collection($stocks));
    }

    public function availableCpo(): JsonResponse
    {
        $stocks = StockCpo::where('status', 'available')
            ->where('movement_type', 'in')
            ->orderBy('stock_date', 'asc')
            ->get();

        return $this->success(StockCpoResource::collection($stocks));
    }

    public function movementCpo(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $movements = StockCpo::select(
                'stock_date',
                'movement_type',
                DB::raw('SUM(quantity) as total_quantity'),
                DB::raw('COUNT(*) as total_transactions')
            )
            ->whereBetween('stock_date', [$startDate, $endDate])
            ->groupBy('stock_date', 'movement_type')
            ->orderBy('stock_date', 'desc')
            ->get();

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'movements' => $movements,
        ]);
    }

    // ======================= KERNEL STOCK =======================

    public function indexKernel(Request $request): JsonResponse
    {
        $query = StockKernel::with('production');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $stocks = $query->orderBy('stock_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($stocks);
    }

    public function storeKernel(StoreStockKernelRequest $request): JsonResponse
    {
        try {
            $stock = StockKernel::create($request->validated());

            return $this->created(
                new StockKernelResource($stock),
                'Kernel stock created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create Kernel stock: ' . $e->getMessage());
        }
    }

    public function summaryKernel(): JsonResponse
    {
        $summary = [
            'total_available' => StockKernel::where('status', 'available')->sum('quantity'),
            'total_sold' => StockKernel::where('status', 'sold')->sum('quantity'),
            'total_transit' => StockKernel::where('status', 'transit')->sum('quantity'),
            'by_quality' => StockKernel::where('status', 'available')
                ->select('quality_grade', DB::raw('SUM(quantity) as total'))
                ->groupBy('quality_grade')
                ->get(),
        ];

        return $this->success($summary);
    }

    // ======================= SHELL STOCK =======================

    public function indexShell(Request $request): JsonResponse
    {
        $query = StockShell::with('production');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $stocks = $query->orderBy('stock_date', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($stocks);
    }

    public function storeShell(StoreStockShellRequest $request): JsonResponse
    {
        try {
            $stock = StockShell::create($request->validated());

            return $this->created(
                new StockShellResource($stock),
                'Shell stock created successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to create Shell stock: ' . $e->getMessage());
        }
    }

    public function summaryShell(): JsonResponse
    {
        $summary = [
            'total_available' => StockShell::where('status', 'available')->sum('quantity'),
            'total_sold' => StockShell::where('status', 'sold')->sum('quantity'),
        ];

        return $this->success($summary);
    }
}
