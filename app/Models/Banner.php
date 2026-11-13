<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use App\Models\Concerns\Scheduled;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Banner extends Model
{
    use LogsActivity, Orderable, Scheduled;

    protected $fillable = ['title', 'subtitle', 'image_id', 'button_label', 'button_url', 'placement', 'campaign_id', 'starts_at', 'ends_at', 'sort_order', 'is_visible'];

    public function image(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'image_id');
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }
}
