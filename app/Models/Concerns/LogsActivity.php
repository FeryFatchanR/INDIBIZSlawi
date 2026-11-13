<?php

namespace App\Models\Concerns;

use App\Models\ActivityLog;

/** Mencatat create/update/delete ke activity_logs (tanpa nilai sensitif). */
trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn ($model) => ActivityLog::record('created', $model));
        static::updated(function ($model) {
            $changed = array_keys($model->getChanges());
            $changed = array_values(array_diff($changed, ['updated_at', 'updated_by', 'lock_version', 'password', 'remember_token']));
            if ($changed !== []) {
                ActivityLog::record('updated', $model, null, ['fields' => $changed]);
            }
        });
        static::deleted(fn ($model) => ActivityLog::record('deleted', $model));
    }

    public function activityLabel(): string
    {
        return (string) ($this->title ?? $this->name ?? $this->company_name ?? $this->label ?? $this->getKey());
    }
}
