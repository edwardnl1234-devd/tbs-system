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

        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('production_date', '>=', $request->start_date);
        }
        if ($request->has('end_date')) {
            $query->whereDate('production_date', '<=', $request->end_date);
        }

        // Filter by period (today, week, month)
        if ($request->has('period')) {
            $today = now()->toDateString();
            switch ($request->period) {
                case 'today':
                    $query->whereDate('production_date', $today);
                    break;
                case 'week':
                    $query->whereDate('production_date', '>=', now()->startOfWeek()->toDateString())
                          ->whereDate('production_date', '<=', $today);
                    break;
                case 'month':
                    $query->whereDate('production_date', '>=', now()->startOfMonth()->toDateString())
                          ->whereDate('production_date', '<=', $today);
                    break;
            }
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

            // Calculate extraction rates
            $tbsInput = $request->tbs_input_weight;
            $cpoOutput = $request->cpo_output ?? 0;
            $kernelOutput = $request->kernel_output ?? 0;
            
            $cpoExtractionRate = $tbsInput > 0 ? round(($cpoOutput / $tbsInput) * 100, 2) : 0;
            $kernelExtractionRate = $tbsInput > 0 ? round(($kernelOutput / $tbsInput) * 100, 2) : 0;

            $production = Production::create([
                'stock_tbs_id' => $request->stock_tbs_id,
                'supervisor_id' => auth()->id(),
                'tbs_input_weight' => $tbsInput,
                'cpo_output' => $cpoOutput,
                'kernel_output' => $kernelOutput,
                'shell_output' => $request->shell_output ?? 0,
                'empty_bunch_output' => $request->empty_bunch_output ?? 0,
                'cpo_extraction_rate' => $cpoExtractionRate,
                'kernel_extraction_rate' => $kernelExtractionRate,
                'production_date' => $request->production_date,
                'shift' => $request->shift,
                'batch_number' => $request->batch_number ?? $batchNumber,
                'status' => 'completed',
                'notes' => $request->notes,
            ]);

            // Create stock entries immediately since status is completed
            $this->createStockEntries($production);

            // Update TBS stock status if linked
            if ($request->stock_tbs_id) {
                StockTbs::where('id', $request->stock_tbs_id)
                    ->update(['status' => 'processed']);
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
                'stock_type' => 'production',
                'status' => 'available',
                'stock_date' => $production->production_date,
            ]);
        }

        // Create Shell stock entry
        if ($production->shell_output > 0) {
            StockShell::create([
                'production_id' => $production->id,
                'quantity' => $production->shell_output,
                'stock_type' => 'production',
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
        $today = now()->toDateString();
        $startOfWeek = now()->startOfWeek()->toDateString();
        $startOfMonth = now()->startOfMonth()->toDateString();
        
        // Determine period filter
        $period = $request->get('period', ''); // '', 'today', 'week', 'month'
        
        $query = Production::query();
        
        switch ($period) {
            case 'today':
                $query->whereDate('production_date', $today);
                break;
            case 'week':
                $query->whereDate('production_date', '>=', $startOfWeek)
                      ->whereDate('production_date', '<=', $today);
                break;
            case 'month':
                $query->whereDate('production_date', '>=', $startOfMonth)
                      ->whereDate('production_date', '<=', $today);
                break;
            // default: no filter, show all
        }
        
        $stats = $query->selectRaw('
                COALESCE(SUM(tbs_input_weight), 0) as total_input,
                COALESCE(SUM(cpo_output), 0) as total_cpo,
                COALESCE(SUM(kernel_output), 0) as total_kernel,
                COALESCE(SUM(shell_output), 0) as total_shell,
                COALESCE(AVG(cpo_extraction_rate), 0) as avg_oer,
                COALESCE(AVG(kernel_extraction_rate), 0) as avg_ker,
                COUNT(*) as total_batches
            ')
            ->first();

        return $this->success([
            'total_input' => $stats->total_input ?? 0,
            'total_cpo' => $stats->total_cpo ?? 0,
            'total_kernel' => $stats->total_kernel ?? 0,
            'total_shell' => $stats->total_shell ?? 0,
            'avg_oer' => round($stats->avg_oer ?? 0, 2),
            'avg_ker' => round($stats->avg_ker ?? 0, 2),
            'total_batches' => $stats->total_batches ?? 0,
            'period' => $period ?: 'all',
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
