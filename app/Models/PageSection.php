<?php

namespace App\Models;

use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageSection extends Model
{
    use HasUuid;

    protected $fillable = [
        'page_id', 'uuid', 'parent_uuid', 'type', 'schema_version', 'content', 'design', 'responsive', 'is_visible', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'content' => 'array',
            'design' => 'array',
            'responsive' => 'array',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'schema_version' => 'integer',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    /** Representasi array netral yang dipakai builder, snapshot, dan renderer. */
    public function toBuilderArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'parent_uuid' => $this->parent_uuid,
            'type' => $this->type,
            'schema_version' => $this->schema_version,
            'content' => $this->content ?? [],
            'design' => $this->design ?? [],
            'responsive' => $this->responsive ?? [],
            'is_visible' => $this->is_visible,
            'sort_order' => $this->sort_order,
        ];
    }
}
