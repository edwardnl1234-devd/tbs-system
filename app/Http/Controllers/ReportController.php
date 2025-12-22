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

class ReportController extends Controller
{
    use ApiResponse;

    public function daily(Request $request): JsonResponse
    {
        $date = $request->get('date', today()->toDateString());

        return $this->success([
            'date' => $date,
            'queue' => $this->getDailyQueueReport($date),
            'weighing' => $this->getDailyWeighingReport($date),
            'production' => $this->getDailyProductionReport($date),
            'sales' => $this->getDailySalesReport($date),
        ]);
    }

    private function getDailyQueueReport(string $date): array
    {
        $queues = Queue::whereDate('arrival_time', $date)->get();

        return [
            'total' => $queues->count(),
            'by_status' => $queues->groupBy('status')->map->count(),
            'by_supplier_type' => $queues->groupBy('supplier_type')->map->count(),
            'avg_wait_time_minutes' => $queues->whereNotNull('call_time')
                ->avg(fn($q) => $q->arrival_time->diffInMinutes($q->call_time)) ?? 0,
        ];
    }

    private function getDailyWeighingReport(string $date): array
    {
        $weighings = Weighing::whereDate('weigh_in_time', $date)->get();

        return [
            'total' => $weighings->count(),
            'completed' => $weighings->where('status', 'completed')->count(),
            'total_bruto' => $weighings->sum('bruto_weight'),
            'total_tara' => $weighings->sum('tara_weight'),
            'total_netto' => $weighings->sum('netto_weight'),
            'total_value' => $weighings->sum('total_price'),
            'by_supplier_type' => $weighings->groupBy(fn($w) => $w->queue?->supplier_type)
                ->map(fn($group) => [
                    'count' => $group->count(),
                    'netto' => $group->sum('netto_weight'),
                    'value' => $group->sum('total_price'),
                ]),
        ];
    }

    private function getDailyProductionReport(string $date): array
    {
        $productions = Production::whereDate('production_date', $date)->get();

        return [
            'batches' => $productions->count(),
            'total_input' => $productions->sum('tbs_processed'),
            'output' => [
                'cpo' => $productions->sum('cpo_produced'),
                'kernel' => $productions->sum('kernel_produced'),
                'shell' => $productions->sum('shell_produced'),
            ],
            'efficiency' => [
                'avg_oer' => round($productions->avg('oer') ?? 0, 2),
                'avg_ker' => round($productions->avg('ker') ?? 0, 2),
            ],
        ];
    }

    private function getDailySalesReport(string $date): array
    {
        $sales = Sales::whereDate('order_date', $date)->get();

        return [
            'total_orders' => $sales->count(),
            'by_product' => $sales->groupBy('product_type')->map(fn($group) => [
                'count' => $group->count(),
                'quantity' => $group->sum('quantity'),
                'revenue' => $group->sum('total_amount'),
            ]),
            'by_status' => $sales->groupBy('status')->map->count(),
            'total_revenue' => $sales->sum('total_amount'),
        ];
    }

    public function weekly(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->startOfWeek()->toDateString());
        $endDate = $request->get('end_date', today()->endOfWeek()->toDateString());

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'weighing' => $this->getWeighingReport($startDate, $endDate),
            'production' => $this->getProductionReport($startDate, $endDate),
            'sales' => $this->getSalesReport($startDate, $endDate),
            'daily_breakdown' => $this->getDailyBreakdown($startDate, $endDate),
        ]);
    }

    public function monthly(Request $request): JsonResponse
    {
        $month = $request->get('month', today()->month);
        $year = $request->get('year', today()->year);
        $startDate = now()->setYear($year)->setMonth($month)->startOfMonth()->toDateString();
        $endDate = now()->setYear($year)->setMonth($month)->endOfMonth()->toDateString();

        return $this->success([
            'period' => ['month' => $month, 'year' => $year],
            'weighing' => $this->getWeighingReport($startDate, $endDate),
            'production' => $this->getProductionReport($startDate, $endDate),
            'sales' => $this->getSalesReport($startDate, $endDate),
            'weekly_breakdown' => $this->getWeeklyBreakdown($startDate, $endDate),
        ]);
    }

    private function getWeighingReport(string $startDate, string $endDate): array
    {
        return Weighing::whereBetween(DB::raw('DATE(weigh_in_time)'), [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed,
                SUM(netto_weight) as total_netto,
                SUM(total_price) as total_value
            ')
            ->first()
            ->toArray();
    }

    private function getProductionReport(string $startDate, string $endDate): array
    {
        return Production::whereBetween('production_date', [$startDate, $endDate])
            ->selectRaw('
                COUNT(*) as batches,
                SUM(tbs_processed) as total_input,
                SUM(cpo_produced) as total_cpo,
                SUM(kernel_produced) as total_kernel,
                SUM(shell_produced) as total_shell,
                AVG(oer) as avg_oer,
                AVG(ker) as avg_ker
            ')
            ->first()
            ->toArray();
    }

    private function getSalesReport(string $startDate, string $endDate): array
    {
        $sales = Sales::whereBetween('order_date', [$startDate, $endDate]);

        return [
            'total_orders' => $sales->count(),
            'total_quantity' => $sales->sum('quantity'),
            'total_revenue' => $sales->sum('total_amount'),
            'by_product' => Sales::whereBetween('order_date', [$startDate, $endDate])
                ->select('product_type', DB::raw('COUNT(*) as count'), DB::raw('SUM(quantity) as quantity'), DB::raw('SUM(total_amount) as revenue'))
                ->groupBy('product_type')
                ->get(),
        ];
    }

    private function getDailyBreakdown(string $startDate, string $endDate): array
    {
        $breakdown = [];
        $current = \Carbon\Carbon::parse($startDate);
        $end = \Carbon\Carbon::parse($endDate);

        while ($current <= $end) {
            $date = $current->toDateString();
            $breakdown[$date] = [
                'weighing' => Weighing::whereDate('weigh_in_time', $date)
                    ->selectRaw('COUNT(*) as count, SUM(netto_weight) as netto')
                    ->first(),
                'production' => Production::whereDate('production_date', $date)
                    ->selectRaw('SUM(tbs_processed) as input, SUM(cpo_produced) as cpo')
                    ->first(),
                'sales' => Sales::whereDate('order_date', $date)
                    ->selectRaw('COUNT(*) as count, SUM(total_amount) as revenue')
                    ->first(),
            ];
            $current->addDay();
        }

        return $breakdown;
    }

    private function getWeeklyBreakdown(string $startDate, string $endDate): array
    {
        $breakdown = [];
        $current = \Carbon\Carbon::parse($startDate)->startOfWeek();
        $end = \Carbon\Carbon::parse($endDate);
        $weekNum = 1;

        while ($current <= $end) {
            $weekEnd = $current->copy()->endOfWeek();
            if ($weekEnd > $end) $weekEnd = $end;

            $breakdown['week_' . $weekNum] = [
                'period' => ['start' => $current->toDateString(), 'end' => $weekEnd->toDateString()],
                'weighing_netto' => Weighing::whereBetween(DB::raw('DATE(weigh_in_time)'), [$current, $weekEnd])
                    ->sum('netto_weight'),
                'production_input' => Production::whereBetween('production_date', [$current, $weekEnd])
                    ->sum('tbs_processed'),
                'sales_revenue' => Sales::whereBetween('order_date', [$current, $weekEnd])
                    ->sum('total_amount'),
            ];

            $current->addWeek();
            $weekNum++;
        }

        return $breakdown;
    }

    public function marginReport(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        // Get purchase data (TBS bought)
        $purchases = Weighing::whereBetween(DB::raw('DATE(weigh_in_time)'), [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('DATE(weigh_in_time) as date, SUM(netto_weight) as weight, SUM(total_price) as cost')
            ->groupBy(DB::raw('DATE(weigh_in_time)'))
            ->get()
            ->keyBy('date');

        // Get sales data
        $sales = Sales::whereBetween('order_date', [$startDate, $endDate])
            ->where('status', 'completed')
            ->selectRaw('order_date as date, SUM(quantity) as quantity, SUM(total_amount) as revenue')
            ->groupBy('order_date')
            ->get()
            ->keyBy('date');

        $totalPurchase = $purchases->sum('cost');
        $totalRevenue = $sales->sum('revenue');
        $grossMargin = $totalRevenue - $totalPurchase;

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => [
                'total_purchase' => $totalPurchase,
                'total_revenue' => $totalRevenue,
                'gross_margin' => $grossMargin,
                'margin_percentage' => $totalPurchase > 0 ? round(($grossMargin / $totalPurchase) * 100, 2) : 0,
            ],
            'purchases' => $purchases,
            'sales' => $sales,
        ]);
    }

    public function stockMovement(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());
        $productType = $request->get('product_type', 'cpo');

        $movements = [];

        switch (strtolower($productType)) {
            case 'cpo':
                $movements = StockCpo::whereBetween('stock_date', [$startDate, $endDate])
                    ->orderBy('stock_date')
                    ->orderBy('id')
                    ->get(['id', 'quantity', 'movement_type', 'reference_number', 'tank_number', 'stock_date', 'status']);
                break;
            case 'kernel':
                $movements = StockKernel::whereBetween('stock_date', [$startDate, $endDate])
                    ->orderBy('stock_date')
                    ->orderBy('id')
                    ->get();
                break;
            case 'shell':
                $movements = StockShell::whereBetween('stock_date', [$startDate, $endDate])
                    ->orderBy('stock_date')
                    ->orderBy('id')
                    ->get();
                break;
            case 'tbs':
                $movements = StockTbs::whereBetween('received_date', [$startDate, $endDate])
                    ->orderBy('received_date')
                    ->orderBy('id')
                    ->get();
                break;
        }

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'product_type' => $productType,
            'movements' => $movements,
        ]);
    }

    public function productionReport(Request $request): JsonResponse
    {
        $startDate = $request->get('start_date', today()->subDays(30)->toDateString());
        $endDate = $request->get('end_date', today()->toDateString());

        $productions = Production::whereBetween('production_date', [$startDate, $endDate])
            ->orderBy('production_date')
            ->get();

        $summary = [
            'total_batches' => $productions->count(),
            'total_input' => $productions->sum('tbs_processed'),
            'total_output' => [
                'cpo' => $productions->sum('cpo_produced'),
                'kernel' => $productions->sum('kernel_produced'),
                'shell' => $productions->sum('shell_produced'),
            ],
            'avg_efficiency' => [
                'oer' => round($productions->avg('oer') ?? 0, 2),
                'ker' => round($productions->avg('ker') ?? 0, 2),
            ],
        ];

        $dailyStats = $productions->groupBy(fn($p) => $p->production_date->toDateString())
            ->map(fn($dayProductions) => [
                'batches' => $dayProductions->count(),
                'input' => $dayProductions->sum('tbs_processed'),
                'cpo' => $dayProductions->sum('cpo_produced'),
                'kernel' => $dayProductions->sum('kernel_produced'),
                'shell' => $dayProductions->sum('shell_produced'),
                'avg_oer' => round($dayProductions->avg('oer'), 2),
            ]);

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'summary' => $summary,
            'daily' => $dailyStats,
            'details' => $productions,
        ]);
    }
}
