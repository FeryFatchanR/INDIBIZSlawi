<?php

namespace App\Models;

use App\Enums\RevisionKind;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Snapshot immutable (meta + seluruh section) sebuah halaman. */
class PageRevision extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = ['page_id', 'version', 'kind', 'note', 'snapshot', 'created_by'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'kind' => RevisionKind::class, 'version' => 'integer', 'created_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => false); // revision bersifat immutable
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return array<int, array<string, mixed>> */
    public function sections(): array
    {
        return $this->snapshot['sections'] ?? [];
    }
}
