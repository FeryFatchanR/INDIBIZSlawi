<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaUsage extends Model
{
    public $timestamps = false;

    protected $fillable = ['media_asset_id', 'usable_type', 'usable_id', 'field'];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }

    public function usable(): MorphTo
    {
        return $this->morphTo();
    }
}
