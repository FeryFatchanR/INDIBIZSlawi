<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStudy extends Model
{
    use LogsActivity, Orderable;

    protected $fillable = ['title', 'slug', 'client_name', 'industry_solution_id', 'summary', 'challenge', 'solution', 'result', 'image_id', 'meta_title', 'meta_description', 'sort_order', 'is_visible'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function industrySolution(): BelongsTo
    {
        return $this->belongsTo(IndustrySolution::class);
    }

    public function publicUrl(): string
    {
        return route('case-studies.show', $this->slug);
    }
}
