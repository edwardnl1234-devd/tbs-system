<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Queue;
use App\Models\Sales;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Models\StockTbs;
use App\Models\Weighing;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    use ApiResponse;

    public function index(): JsonResponse
    {
        return $this->success([
            'queue' => $this->getQueueStats(),
            'weighing' => $this->getWeighingStats(),
            'production' => $this->getProductionStats(),
            'stock' => $this->getStockSummary(),
            'sales' => $this->getSalesStats(),
        ]);
    }

    public function queueStats(): JsonResponse
    {
        return $this->success($this->getQueueStats());
    }

    private function getQueueStats(): array
    {
        $today = today();

        return [
            'waiting' => Queue::whereDate('arrival_time', $today)->waiting()->count(),
            'processing' => Queue::whereDate('arrival_time', $today)->processing()->count(),
            'completed' => Queue::whereDate('arrival_time', $today)->where('status', 'completed')->count(),
            'cancelled' => Queue::whereDate('arrival_time', $today)->where('status', 'cancelled')->count(),
            'total_today' => Queue::whereDate('arrival_time', $today)->count(),
            'avg_wait_time' => Queue::whereDate('arrival_time', $today)
                ->whereNotNull('call_time')
                ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, arrival_time, call_time)) as avg')
                ->value('avg') ?? 0,
        ];
    }

    public function productionStats(): JsonResponse
    {
        return $this->success($this->getProductionStats());
    }

    private function getProductionStats(): array
    {
        $today = today();
        $todayProduction = Production::whereDate('production_date', $today);

        return [
            'total_input' => $todayProduction->sum('tbs_input_weight'),
            'total_cpo' => $todayProduction->sum('cpo_output'),
            'total_kernel' => $todayProduction->sum('kernel_output'),
            'total_shell' => $todayProduction->sum('shell_output'),
            'oer' => $todayProduction->avg('cpo_extraction_rate') ?? 0,
            'ker' => $todayProduction->avg('kernel_extraction_rate') ?? 0,
            'batches' => $todayProduction->count(),
        ];
    }

    public function stockSummary(): JsonResponse
    {
        return $this->success($this->getStockSummary());
    }

    private function getStockSummary(): array
    {
        return [
            'tbs' => [
                'total' => StockTbs::where('status', 'ready')->sum('quantity'),
                'processing' => StockTbs::where('status', 'processing')->sum('quantity'),
            ],
            'cpo' => [
                'available' => StockCpo::where('status', 'available')
                    ->where('movement_type', 'in')
                    ->sum('quantity'),
                'reserved' => StockCpo::where('status', 'reserved')
                    ->where('movement_type', 'in')
                    ->sum('quantity'),
                'from_production' => StockCpo::where('status', 'available')
                    ->where('movement_type', 'in')
                    ->where('stock_type', 'production')
                    ->sum('quantity'),
                'from_purchase' => StockCpo::where('status', 'available')
                    ->where('movement_type', 'in')
                    ->where('stock_type', 'purchase')
                    ->sum('quantity'),
            ],
            'kernel' => [
                'available' => StockKernel::where('status', 'available')->sum('quantity'),
                'sold' => StockKernel::where('status', 'sold')->sum('quantity'),
                'from_production' => StockKernel::where('status', 'available')
                    ->where('stock_type', 'production')
                    ->sum('quantity'),
                'from_purchase' => StockKernel::where('status', 'available')
                    ->where('stock_type', 'purchase')
                    ->sum('quantity'),
            ],
            'shell' => [
                'available' => StockShell::where('status', 'available')->sum('quantity'),
                'sold' => StockShell::where('status', 'sold')->sum('quantity'),
                'from_production' => StockShell::where('status', 'available')
                    ->where('stock_type', 'production')
                    ->sum('quantity'),
                'from_purchase' => StockShell::where('status', 'available')
                    ->where('stock_type', 'purchase')
                    ->sum('quantity'),
            ],
        ];
    }

    public function salesStats(): JsonResponse
    {
        return $this->success($this->getSalesStats());
    }

    private function getSalesStats(): array
    {
        $today = today();

        return [
            'pending' => Sales::where('status', 'pending')->count(),
            'delivered' => Sales::where('status', 'delivered')->count(),
            'completed' => Sales::where('status', 'completed')->count(),
            'today_revenue' => Sales::whereDate('order_date', $today)->sum('total_amount'),
            'total_revenue' => Sales::sum('total_amount'),
            'total_transactions' => Sales::whereDate('order_date', $today)->count(),
            'month_revenue' => Sales::whereMonth('order_date', $today->month)
                ->whereYear('order_date', $today->year)
                ->sum('total_amount'),
        ];
    }

    private function getWeighingStats(): array
    {
        $today = today();

        return [
            'total_weighed' => Weighing::whereDate('weigh_in_time', $today)->count(),
            'completed' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'completed')->count(),
            'in_progress' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'weigh_in')->count(),
            'total_netto' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'completed')->sum('netto_weight'),
        ];
    }

    public function margin(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        // Get total revenue from sales
        $revenue = Sales::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_amount');

        // Get total TBS purchase cost
        $purchaseCost = Weighing::whereBetween(DB::raw('DATE(weigh_in_time)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('total_price');

        // Calculate margin
        $margin = $revenue - $purchaseCost;
        $marginPercentage = $purchaseCost > 0 ? ($margin / $purchaseCost) * 100 : 0;

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'revenue' => $revenue,
            'purchase_cost' => $purchaseCost,
            'margin' => $margin,
            'margin_percentage' => round($marginPercentage, 2),
        ]);
    }

    public function efficiency(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $production = Production::whereBetween('production_date', [$startDate, $endDate])
            ->selectRaw('
                SUM(tbs_input_weight) as total_input,
                SUM(cpo_output) as total_cpo,
                SUM(kernel_output) as total_kernel,
                SUM(shell_output) as total_shell,
                AVG(cpo_extraction_rate) as avg_oer,
                AVG(kernel_extraction_rate) as avg_ker
            ')
            ->first();

        $totalInput = $production->total_input ?? 0;
        $totalCpo = $production->total_cpo ?? 0;
        $totalKernel = $production->total_kernel ?? 0;
        $totalShell = $production->total_shell ?? 0;
        
        $totalOutput = $totalCpo + $totalKernel + $totalShell;
        $overallEfficiency = $totalInput > 0 
            ? ($totalOutput / $totalInput) * 100 
            : 0;

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'total_input_kg' => $totalInput,
            'total_cpo_kg' => $totalCpo,
            'total_kernel_kg' => $totalKernel,
            'total_shell_kg' => $totalShell,
            'avg_oer' => round($production->avg_oer ?? 0, 2),
            'avg_ker' => round($production->avg_ker ?? 0, 2),
            'overall_efficiency' => round($overallEfficiency, 2),
        ]);
    }
}
