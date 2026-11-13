<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Testimonial extends Model
{
    use LogsActivity, Orderable;

    protected $fillable = ['name', 'position', 'company', 'quote', 'rating', 'photo_id', 'sort_order', 'is_visible'];

    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'photo_id');
    }
}
