<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait LogsActivity
{
    /**
     * Temporary storage for old attributes (not persisted to database)
     */
    protected static array $oldAttributesCache = [];

    /**
     * Boot the trait and register model events.
     */
    public static function bootLogsActivity(): void
    {
        static::created(function (Model $model) {
            $model->logActivity('created', null, $model->getAttributes());
        });

        static::updating(function (Model $model) {
            // Simpan nilai lama sebelum update ke static cache (bukan property model)
            static::$oldAttributesCache[$model->getKey()] = $model->getOriginal();
        });

        static::updated(function (Model $model) {
            $oldValues = static::$oldAttributesCache[$model->getKey()] ?? [];
            $newValues = $model->getChanges();

            // Hapus timestamp dari log
            unset($newValues['updated_at'], $oldValues['updated_at']);

            if (!empty($newValues)) {
                // Filter old values untuk hanya menyimpan field yang berubah
                $filteredOld = array_intersect_key($oldValues, $newValues);
                $model->logActivity('updated', $filteredOld, $newValues);
            }
            
            // Cleanup cache
            unset(static::$oldAttributesCache[$model->getKey()]);
        });

        static::deleted(function (Model $model) {
            $model->logActivity('deleted', $model->getAttributes(), null);
        });
    }

    /**
     * Log an activity.
     */
    public function logActivity(string $action, ?array $oldValues, ?array $newValues): ActivityLog
    {
        $description = $this->getActivityDescription($action);

        return ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'model_type' => get_class($this),
            'model_id' => $this->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'description' => $description,
        ]);
    }

    /**
     * Get activity description.
     */
    protected function getActivityDescription(string $action): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getActivityIdentifier();

        return match ($action) {
            'created' => "{$modelName} {$identifier} telah dibuat",
            'updated' => "{$modelName} {$identifier} telah diubah",
            'deleted' => "{$modelName} {$identifier} telah dihapus",
            default => "{$modelName} {$identifier}: {$action}",
        };
    }

    /**
     * Get identifier for the activity log.
     * Override this method in your model to customize.
     */
    protected function getActivityIdentifier(): string
    {
        // Coba cari field yang bisa jadi identifier
        $identifierFields = ['name', 'title', 'so_number', 'queue_number', 'license_plate', 'email'];

        foreach ($identifierFields as $field) {
            if (isset($this->attributes[$field])) {
                return "'{$this->attributes[$field]}'";
            }
        }

        return "#{$this->getKey()}";
    }

    /**
     * Get the activity logs for this model.
     */
    public function activityLogs()
    {
        return ActivityLog::where('model_type', get_class($this))
            ->where('model_id', $this->getKey())
            ->orderBy('created_at', 'desc');
    }

    /**
     * Fields to exclude from logging.
     * Override in model to customize.
     */
    protected function getExcludedLogFields(): array
    {
        return ['password', 'remember_token', 'updated_at', 'created_at'];
    }
}
