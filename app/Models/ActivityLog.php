<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    public const UPDATED_AT = null;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['properties' => 'array', 'created_at' => 'datetime'];
    }

    public static function record(string $event, ?Model $subject = null, ?string $description = null, array $properties = []): self
    {
        $request = app()->runningInConsole() ? null : request();

        return static::query()->create([
            'user_id' => Auth::id(),
            'event' => $event,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'description' => $description ?? ($subject ? class_basename($subject).': '.(method_exists($subject, 'activityLabel') ? $subject->activityLabel() : $subject->getKey()) : null),
            'properties' => $properties ?: null,
            'ip_hash' => $request?->ip() ? hash_hmac('sha256', $request->ip(), (string) config('app.key')) : null,
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
