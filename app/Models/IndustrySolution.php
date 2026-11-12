<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustrySolution extends Model
{
    use LogsActivity, Orderable;

    protected $fillable = [
        'name', 'slug', 'icon', 'short_description', 'description', 'image_id', 'meta_title', 'meta_description',
        'sort_order', 'is_visible',
    ];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class);
    }

    public function caseStudies(): HasMany
    {
        return $this->hasMany(CaseStudy::class);
    }

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function publicUrl(): string
    {
        return route('solutions.show', $this->slug);
    }
}
