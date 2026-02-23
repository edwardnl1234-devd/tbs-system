<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of activity logs.
     */
    public function index(Request $request): JsonResponse
    {
        $query = ActivityLog::with('user');

        // Filter by action
        if ($request->has('action')) {
            $query->where('action', $request->action);
        }

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by model type
        if ($request->has('model_type')) {
            $modelType = $request->model_type;
            // Allow short names like "Sales" or full class names
            if (!str_contains($modelType, '\\')) {
                $modelType = "App\\Models\\{$modelType}";
            }
            $query->where('model_type', $modelType);
        }

        // Filter by model ID
        if ($request->has('model_id')) {
            $query->where('model_id', $request->model_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Search in description
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQ) use ($search) {
                        $userQ->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $logs = $query->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 25);

        // Transform the results
        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'action' => $log->action,
                'action_label' => $log->getActionLabel(),
                'model_type' => $log->getModelName(),
                'model_id' => $log->model_id,
                'old_values' => $log->old_values,
                'new_values' => $log->new_values,
                'changes' => $log->getChangedAttributes(),
                'description' => $log->description,
                'ip_address' => $log->ip_address,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        });

        return $this->successPaginated($logs);
    }

    /**
     * Display a specific activity log.
     */
    public function show(int $id): JsonResponse
    {
        $log = ActivityLog::with('user')->find($id);

        if (!$log) {
            return $this->notFound('Activity log not found');
        }

        return $this->success([
            'id' => $log->id,
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'action' => $log->action,
            'action_label' => $log->getActionLabel(),
            'model_type' => $log->getModelName(),
            'model_id' => $log->model_id,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'changes' => $log->getChangedAttributes(),
            'description' => $log->description,
            'ip_address' => $log->ip_address,
            'user_agent' => $log->user_agent,
            'created_at' => $log->created_at->format('Y-m-d H:i:s'),
            'created_at_human' => $log->created_at->diffForHumans(),
        ]);
    }

    /**
     * Get activity logs for a specific model.
     */
    public function forModel(Request $request, string $modelType, int $modelId): JsonResponse
    {
        $fullModelType = "App\\Models\\{$modelType}";

        $logs = ActivityLog::with('user')
            ->where('model_type', $fullModelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 15);

        $logs->getCollection()->transform(function ($log) {
            return [
                'id' => $log->id,
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                ] : null,
                'action' => $log->action,
                'action_label' => $log->getActionLabel(),
                'changes' => $log->getChangedAttributes(),
                'description' => $log->description,
                'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                'created_at_human' => $log->created_at->diffForHumans(),
            ];
        });

        return $this->successPaginated($logs);
    }

    /**
     * Get recent activities.
     */
    public function recent(Request $request): JsonResponse
    {
        $limit = $request->limit ?? 10;

        $logs = ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user' => $log->user ? $log->user->name : 'System',
                    'action' => $log->action,
                    'action_label' => $log->getActionLabel(),
                    'model_type' => $log->getModelName(),
                    'model_id' => $log->model_id,
                    'description' => $log->description,
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'created_at_human' => $log->created_at->diffForHumans(),
                ];
            });

        return $this->success($logs);
    }

    /**
     * Get activity statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $days = $request->days ?? 7;

        $stats = [
            'by_action' => ActivityLog::selectRaw('action, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('action')
                ->pluck('count', 'action'),

            'by_model' => ActivityLog::selectRaw('model_type, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('model_type')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [class_basename($item->model_type) => $item->count];
                }),

            'by_user' => ActivityLog::with('user')
                ->selectRaw('user_id, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays($days))
                ->whereNotNull('user_id')
                ->groupBy('user_id')
                ->orderBy('count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'user' => $item->user ? $item->user->name : 'Unknown',
                        'count' => $item->count,
                    ];
                }),

            'total' => ActivityLog::where('created_at', '>=', now()->subDays($days))->count(),

            'today' => ActivityLog::whereDate('created_at', today())->count(),
        ];

        return $this->success($stats);
    }
}
