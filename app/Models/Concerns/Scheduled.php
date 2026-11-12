<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Konten dengan jendela waktu tayang starts_at/ends_at. */
trait Scheduled
{
    public function initializeScheduled(): void
    {
        $this->mergeCasts(['starts_at' => 'datetime', 'ends_at' => 'datetime']);
    }

    public function scopeRunning(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(fn (Builder $q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn (Builder $q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
