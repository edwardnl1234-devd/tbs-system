<?php

namespace App\Http\Controllers;

use App\Models\Production;
use App\Models\Queue;
use App\Models\Sales;
use App\Models\StockCpo;
use App\Models\StockKernel;
use App\Models\StockShell;
use App\Models\Weighing;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PollingController extends Controller
{
    use ApiResponse;

    /**
     * Get real-time queue updates for display screens
     */
    public function queue(): JsonResponse
    {
        $today = today();

        // Get current active queues
        $waitingQueues = Queue::with(['truck', 'supplier'])
            ->whereDate('arrival_time', $today)
            ->where('status', 'waiting')
            ->orderBy('queue_number')
            ->limit(20)
            ->get()
            ->map(fn($q) => [
                'id' => $q->id,
                'queue_number' => $q->queue_number,
                'truck_plate' => $q->truck->plate_number ?? null,
                'supplier_name' => $q->supplier->name ?? null,
                'supplier_type' => $q->supplier_type,
                'bank' => $q->bank,
                'arrival_time' => $q->arrival_time->format('H:i'),
                'estimated_call' => $q->estimated_call_time?->format('H:i'),
            ]);

        $processingQueues = Queue::with(['truck', 'supplier'])
            ->whereDate('arrival_time', $today)
            ->where('status', 'processing')
            ->orderBy('call_time')
            ->get()
            ->map(fn($q) => [
                'id' => $q->id,
                'queue_number' => $q->queue_number,
                'truck_plate' => $q->truck->plate_number ?? null,
                'bank' => $q->bank,
                'call_time' => $q->call_time?->format('H:i'),
            ]);

        return $this->success([
            'timestamp' => now()->toISOString(),
            'waiting' => $waitingQueues,
            'waiting_count' => Queue::whereDate('arrival_time', $today)->waiting()->count(),
            'processing' => $processingQueues,
            'processing_count' => $processingQueues->count(),
            'completed_today' => Queue::whereDate('arrival_time', $today)->where('status', 'completed')->count(),
        ]);
    }

    /**
     * Get real-time weighing status
     */
    public function weighing(): JsonResponse
    {
        $today = today();

        // Get current weighing in progress
        $inProgress = Weighing::with(['queue.truck', 'queue.supplier'])
            ->whereDate('weigh_in_time', $today)
            ->where('status', 'weigh_in')
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'ticket_number' => $w->ticket_number,
                'queue_number' => $w->queue->queue_number ?? null,
                'truck_plate' => $w->queue->truck->plate_number ?? null,
                'bruto_weight' => $w->bruto_weight,
                'weigh_in_time' => $w->weigh_in_time->format('H:i'),
            ]);

        // Recent completions
        $recentCompleted = Weighing::with(['queue.truck'])
            ->whereDate('weigh_in_time', $today)
            ->where('status', 'completed')
            ->orderBy('weigh_out_time', 'desc')
            ->limit(5)
            ->get()
            ->map(fn($w) => [
                'id' => $w->id,
                'ticket_number' => $w->ticket_number,
                'truck_plate' => $w->queue->truck->plate_number ?? null,
                'netto_weight' => $w->netto_weight,
                'completed_at' => $w->weigh_out_time->format('H:i'),
            ]);

        return $this->success([
            'timestamp' => now()->toISOString(),
            'in_progress' => $inProgress,
            'recent_completed' => $recentCompleted,
            'today_stats' => [
                'total' => Weighing::whereDate('weigh_in_time', $today)->count(),
                'completed' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'completed')->count(),
                'total_netto' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'completed')->sum('netto_weight'),
            ],
        ]);
    }

    /**
     * Get real-time stock levels
     */
    public function stock(): JsonResponse
    {
        return $this->success([
            'timestamp' => now()->toISOString(),
            'cpo' => [
                'available' => StockCpo::where('status', 'available')->where('movement_type', 'in')->sum('quantity'),
                'reserved' => StockCpo::where('status', 'reserved')->where('movement_type', 'in')->sum('quantity'),
                'by_tank' => StockCpo::where('status', 'available')
                    ->where('movement_type', 'in')
                    ->whereNotNull('tank_number')
                    ->select('tank_number', \DB::raw('SUM(quantity) as total'))
                    ->groupBy('tank_number')
                    ->get(),
            ],
            'kernel' => [
                'available' => StockKernel::where('status', 'available')->sum('quantity'),
                'reserved' => StockKernel::where('status', 'reserved')->sum('quantity'),
            ],
            'shell' => [
                'available' => StockShell::where('status', 'available')->sum('quantity'),
                'reserved' => StockShell::where('status', 'reserved')->sum('quantity'),
            ],
        ]);
    }

    /**
     * Get real-time dashboard metrics
     */
    public function dashboard(): JsonResponse
    {
        $today = today();

        return $this->success([
            'timestamp' => now()->toISOString(),
            'queue' => [
                'waiting' => Queue::whereDate('arrival_time', $today)->waiting()->count(),
                'processing' => Queue::whereDate('arrival_time', $today)->processing()->count(),
            ],
            'weighing' => [
                'in_progress' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'weigh_in')->count(),
                'completed' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'completed')->count(),
                'total_netto' => Weighing::whereDate('weigh_in_time', $today)->where('status', 'completed')->sum('netto_weight'),
            ],
            'production' => [
                'batches' => Production::whereDate('production_date', $today)->count(),
                'total_input' => Production::whereDate('production_date', $today)->sum('tbs_processed'),
                'total_cpo' => Production::whereDate('production_date', $today)->sum('cpo_produced'),
            ],
            'sales' => [
                'pending' => Sales::where('status', 'pending')->count(),
                'today_revenue' => Sales::whereDate('order_date', $today)->sum('total_amount'),
            ],
        ]);
    }

    /**
     * Get real-time production status
     */
    public function production(): JsonResponse
    {
        $today = today();

        $todayProduction = Production::whereDate('production_date', $today)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'batch_number' => $p->batch_number,
                'tbs_processed' => $p->tbs_processed,
                'cpo_produced' => $p->cpo_produced,
                'kernel_produced' => $p->kernel_produced,
                'oer' => $p->oer,
                'status' => $p->status,
                'shift' => $p->shift,
            ]);

        return $this->success([
            'timestamp' => now()->toISOString(),
            'recent_batches' => $todayProduction,
            'today_summary' => [
                'batches' => Production::whereDate('production_date', $today)->count(),
                'total_input' => Production::whereDate('production_date', $today)->sum('tbs_processed'),
                'total_cpo' => Production::whereDate('production_date', $today)->sum('cpo_produced'),
                'total_kernel' => Production::whereDate('production_date', $today)->sum('kernel_produced'),
                'avg_oer' => round(Production::whereDate('production_date', $today)->avg('oer') ?? 0, 2),
            ],
        ]);
    }
}
