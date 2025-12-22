<?php

namespace App\Http\Controllers;

use App\Http\Requests\Production\StoreProductionRequest;
use App\Http\Requests\Production\UpdateProductionRequest;
use App\Http\Resources\ProductionResource;
use App\Models\Production;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Models\StockTbs;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Production::with(['stockTbs', 'supervisor']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('date')) {
            $query->whereDate('production_date', $request->date);
        }

        if ($request->has('shift')) {
            $query->where('shift', $request->shift);
        }

        $productions = $query->orderBy('production_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($productions);
    }

    public function store(StoreProductionRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate batch number
            $batchNumber = 'PRD' . now()->format('YmdHis') . str_pad(random_int(1, 999), 3, '0', STR_PAD_LEFT);

            $production = Production::create([
                'stock_tbs_id' => $request->stock_tbs_id,
                'supervisor_id' => auth()->id(),
                'tbs_input_weight' => $request->tbs_input_weight,
                'cpo_output' => $request->cpo_output ?? 0,
                'kernel_output' => $request->kernel_output ?? 0,
                'shell_output' => $request->shell_output ?? 0,
                'empty_bunch_output' => $request->empty_bunch_output ?? 0,
                'production_date' => $request->production_date,
                'shift' => $request->shift,
                'batch_number' => $request->batch_number ?? $batchNumber,
                'status' => 'processing',
                'notes' => $request->notes,
            ]);

            // Update TBS stock status if linked
            if ($request->stock_tbs_id) {
                StockTbs::where('id', $request->stock_tbs_id)
                    ->update(['status' => 'processing']);
            }

            DB::commit();

            return $this->created(
                new ProductionResource($production->load(['stockTbs', 'supervisor'])),
                'Production record created successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create production: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $production = Production::with(['stockTbs', 'supervisor', 'stockCpo', 'stockKernel', 'stockShell'])->find($id);

        if (!$production) {
            return $this->notFound('Production not found');
        }

        return $this->success(new ProductionResource($production));
    }

    public function update(UpdateProductionRequest $request, int $id): JsonResponse
    {
        try {
            DB::beginTransaction();

            $production = Production::find($id);

            if (!$production) {
                return $this->notFound('Production not found');
            }

            $previousStatus = $production->status;
            $production->update($request->validated());

            // Calculate extraction rates
            if ($production->tbs_input_weight > 0) {
                $production->cpo_extraction_rate = ($production->cpo_output / $production->tbs_input_weight) * 100;
                $production->kernel_extraction_rate = ($production->kernel_output / $production->tbs_input_weight) * 100;
                $production->save();
            }

            // If status changed to completed, create stock entries
            if ($previousStatus !== 'completed' && $production->status === 'completed') {
                $this->createStockEntries($production);
            }

            DB::commit();

            return $this->success(
                new ProductionResource($production->load(['stockTbs', 'supervisor'])),
                'Production updated successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to update production: ' . $e->getMessage());
        }
    }

    private function createStockEntries(Production $production): void
    {
        // Create CPO stock entry
        if ($production->cpo_output > 0) {
            StockCpo::create([
                'production_id' => $production->id,
                'quantity' => $production->cpo_output,
                'quality_grade' => 'standard',
                'stock_type' => 'production',
                'movement_type' => 'in',
                'reference_number' => $production->batch_number,
                'stock_date' => $production->production_date,
                'status' => 'available',
            ]);
        }

        // Create Kernel stock entry
        if ($production->kernel_output > 0) {
            StockKernel::create([
                'production_id' => $production->id,
                'quantity' => $production->kernel_output,
                'status' => 'available',
                'stock_date' => $production->production_date,
            ]);
        }

        // Create Shell stock entry
        if ($production->shell_output > 0) {
            StockShell::create([
                'production_id' => $production->id,
                'quantity' => $production->shell_output,
                'status' => 'available',
                'stock_date' => $production->production_date,
            ]);
        }

        // Update TBS stock status to processed
        if ($production->stock_tbs_id) {
            StockTbs::where('id', $production->stock_tbs_id)
                ->update([
                    'status' => 'processed',
                    'processed_date' => $production->production_date,
                ]);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $production = Production::find($id);

            if (!$production) {
                return $this->notFound('Production not found');
            }

            if ($production->status === 'completed') {
                return $this->error('Cannot delete a completed production record', 400);
            }

            $production->delete();

            return $this->success(null, 'Production deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete production: ' . $e->getMessage());
        }
    }

    public function today(): JsonResponse
    {
        $productions = Production::with(['stockTbs', 'supervisor'])
            ->whereDate('production_date', today())
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(ProductionResource::collection($productions));
    }

    public function byDate(string $date): JsonResponse
    {
        $productions = Production::with(['stockTbs', 'supervisor'])
            ->whereDate('production_date', $date)
            ->orderBy('created_at', 'desc')
            ->get();

        return $this->success(ProductionResource::collection($productions));
    }

    public function statistics(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $stats = Production::whereBetween('production_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('
                SUM(tbs_input_weight) as total_tbs_input,
                SUM(cpo_output) as total_cpo_output,
                SUM(kernel_output) as total_kernel_output,
                SUM(shell_output) as total_shell_output,
                SUM(empty_bunch_output) as total_empty_bunch_output,
                AVG(cpo_extraction_rate) as avg_cpo_extraction_rate,
                AVG(kernel_extraction_rate) as avg_kernel_extraction_rate,
                COUNT(*) as total_batches
            ')
            ->first();

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'statistics' => $stats,
        ]);
    }

    public function efficiency(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $dailyEfficiency = Production::select(
                'production_date',
                DB::raw('SUM(tbs_input_weight) as tbs_input'),
                DB::raw('SUM(cpo_output) as cpo_output'),
                DB::raw('SUM(kernel_output) as kernel_output'),
                DB::raw('AVG(cpo_extraction_rate) as cpo_rate'),
                DB::raw('AVG(kernel_extraction_rate) as kernel_rate')
            )
            ->where('status', 'completed')
            ->whereBetween('production_date', [$startDate, $endDate])
            ->groupBy('production_date')
            ->orderBy('production_date', 'desc')
            ->get();

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'daily_efficiency' => $dailyEfficiency,
        ]);
    }
}
