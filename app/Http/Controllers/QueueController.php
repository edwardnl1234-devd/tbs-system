<?php

namespace App\Http\Controllers;

use App\Http\Requests\Queue\StoreQueueRequest;
use App\Http\Requests\Queue\UpdateQueueRequest;
use App\Http\Requests\Queue\UpdateQueueStatusRequest;
use App\Http\Resources\QueueResource;
use App\Models\Queue;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QueueController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Queue::with(['truck', 'supplier']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('bank')) {
            $query->where('bank', $request->bank);
        }

        if ($request->has('supplier_type')) {
            $query->where('supplier_type', $request->supplier_type);
        }

        if ($request->has('date')) {
            $query->whereDate('arrival_time', $request->date);
        }

        $queues = $query->orderBy('priority', 'desc')
            ->orderBy('arrival_time', 'asc')
            ->paginate($request->per_page ?? 15);

        return $this->successPaginated($queues);
    }

    public function store(StoreQueueRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Generate queue number: QYYYYMMDDnnn
            $today = now()->format('Ymd');
            $lastQueue = Queue::whereDate('arrival_time', today())
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastQueue ? (int) substr($lastQueue->queue_number, -3) + 1 : 1;
            $queueNumber = 'Q' . $today . str_pad($sequence, 3, '0', STR_PAD_LEFT);

            // Calculate estimated process time based on average
            $avgProcessTime = Queue::where('status', 'completed')
                ->whereDate('arrival_time', '>=', now()->subDays(7))
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, arrival_time, updated_at)')) ?? 30;

            $waitingCount = Queue::whereIn('status', ['waiting', 'processing'])->count();
            $estimatedTime = now()->addMinutes(intval($avgProcessTime * $waitingCount));

            $queue = Queue::create([
                'truck_id' => $request->truck_id,
                'supplier_id' => $request->supplier_id,
                'queue_number' => $queueNumber,
                'supplier_type' => $request->supplier_type ?? 'umum',
                'bank' => $request->bank,
                'arrival_time' => now(),
                'estimated_call_time' => $estimatedTime,
                'status' => 'waiting',
                'priority' => $request->priority ?? 0,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return $this->created(
                new QueueResource($queue->load('truck')),
                'Queue entry created successfully'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverError('Failed to create queue: ' . $e->getMessage());
        }
    }

    public function show(int $id): JsonResponse
    {
        $queue = Queue::with(['truck', 'supplier', 'weighing'])->find($id);

        if (!$queue) {
            return $this->notFound('Queue not found');
        }

        return $this->success(new QueueResource($queue));
    }

    public function update(UpdateQueueRequest $request, int $id): JsonResponse
    {
        try {
            $queue = Queue::find($id);

            if (!$queue) {
                return $this->notFound('Queue not found');
            }

            $queue->update($request->validated());

            return $this->success(
                new QueueResource($queue->load(['truck', 'supplier'])),
                'Queue updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update queue: ' . $e->getMessage());
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $queue = Queue::find($id);

            if (!$queue) {
                return $this->notFound('Queue not found');
            }

            if ($queue->status === 'processing') {
                return $this->error('Cannot delete a queue that is being processed', 400);
            }

            $queue->delete();

            return $this->success(null, 'Queue deleted successfully');
        } catch (\Exception $e) {
            return $this->serverError('Failed to delete queue: ' . $e->getMessage());
        }
    }

    public function updateStatus(UpdateQueueStatusRequest $request, int $id): JsonResponse
    {
        try {
            $queue = Queue::find($id);

            if (!$queue) {
                return $this->notFound('Queue not found');
            }

            $queue->update(['status' => $request->status]);

            return $this->success(
                new QueueResource($queue->load(['truck', 'supplier'])),
                'Queue status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverError('Failed to update queue status: ' . $e->getMessage());
        }
    }

    public function active(): JsonResponse
    {
        $queues = Queue::with(['truck', 'supplier'])
            ->whereIn('status', ['waiting', 'processing'])
            ->orderBy('priority', 'desc')
            ->orderBy('arrival_time', 'asc')
            ->get();

        return $this->success(QueueResource::collection($queues));
    }

    public function processing(): JsonResponse
    {
        $queues = Queue::with(['truck', 'supplier'])
            ->where('status', 'processing')
            ->orderBy('arrival_time', 'asc')
            ->get();

        return $this->success(QueueResource::collection($queues));
    }

    public function byBank(int $bank): JsonResponse
    {
        $queues = Queue::with(['truck', 'supplier'])
            ->where('bank', $bank)
            ->whereIn('status', ['waiting', 'processing'])
            ->orderBy('priority', 'desc')
            ->orderBy('arrival_time', 'asc')
            ->get();

        return $this->success(QueueResource::collection($queues));
    }

    public function today(): JsonResponse
    {
        $queues = Queue::with(['truck', 'supplier'])
            ->whereDate('arrival_time', today())
            ->orderBy('arrival_time', 'asc')
            ->get();

        return $this->success(QueueResource::collection($queues));
    }

    public function statistics(): JsonResponse
    {
        $today = today();

        $stats = [
            'total_today' => Queue::whereDate('arrival_time', $today)->count(),
            'waiting' => Queue::whereDate('arrival_time', $today)->where('status', 'waiting')->count(),
            'processing' => Queue::whereDate('arrival_time', $today)->where('status', 'processing')->count(),
            'completed' => Queue::whereDate('arrival_time', $today)->where('status', 'completed')->count(),
            'cancelled' => Queue::whereDate('arrival_time', $today)->where('status', 'cancelled')->count(),
            'by_bank' => [
                'bank_1' => Queue::whereDate('arrival_time', $today)->where('bank', 1)->count(),
                'bank_2' => Queue::whereDate('arrival_time', $today)->where('bank', 2)->count(),
            ],
            'average_wait_time_minutes' => Queue::where('status', 'completed')
                ->whereDate('arrival_time', $today)
                ->avg(DB::raw('TIMESTAMPDIFF(MINUTE, arrival_time, updated_at)')) ?? 0,
        ];

        return $this->success($stats);
    }
}
