<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Scheduled;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use LogsActivity, Scheduled;

    protected $fillable = [
        'name', 'slug', 'utm_source', 'utm_medium', 'utm_campaign', 'whatsapp_message', 'description',
        'starts_at', 'ends_at', 'is_active',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->running();
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function whatsappClicks(): HasMany
    {
        return $this->hasMany(WhatsappClick::class);
    }
}
