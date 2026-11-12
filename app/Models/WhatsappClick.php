<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappClick extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['campaign_id', 'product_id', 'source_path', 'ip_hash', 'user_agent'];

    protected $hidden = ['ip_hash'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
