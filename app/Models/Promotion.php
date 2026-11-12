<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use App\Models\Concerns\Scheduled;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use LogsActivity, Orderable, Scheduled;

    protected $fillable = ['title', 'description', 'image_id', 'product_id', 'campaign_id', 'badge', 'starts_at', 'ends_at', 'sort_order', 'is_visible'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
