<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Konten yang bisa diurutkan (sort_order) dan disembunyikan (is_visible). */
trait Orderable
{
    public static function bootOrderable(): void
    {
        static::creating(function ($model) {
            if ($model->sort_order === null || $model->sort_order === 0) {
                $model->sort_order = (int) static::query()->max('sort_order') + 1;
            }
        });
    }

    public function initializeOrderable(): void
    {
        $this->mergeCasts(['sort_order' => 'integer', 'is_visible' => 'boolean']);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where($this->qualifyColumn('is_visible'), true)
            ->orderBy($this->qualifyColumn('sort_order'))
            ->orderBy($this->qualifyColumn('id'));
    }
}
