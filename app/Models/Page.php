<?php

namespace App\Models;

use App\Enums\PageStatus;
use App\Enums\PageType;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Halaman CMS. `page_sections` = draft kerja; publik membaca snapshot dari `published_revision_id`.
 * Kolom status/publish/lock_version sengaja tidak fillable — hanya diubah melalui Action CMS.
 */
class Page extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'title', 'slug', 'type', 'meta_title', 'meta_description', 'canonical_url', 'og_image_id', 'noindex',
    ];

    protected function casts(): array
    {
        return [
            'type' => PageType::class,
            'status' => PageStatus::class,
            'noindex' => 'boolean',
            'lock_version' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $page) {
            $page->status ??= PageStatus::Draft;
            $page->type ??= PageType::Page;
            $page->created_by ??= auth()->id();
        });
        static::saving(function (self $page) {
            if (auth()->check()) {
                $page->updated_by = auth()->id();
            }
        });
    }

    public function sections(): HasMany
    {
        return $this->hasMany(PageSection::class)->orderBy('sort_order')->orderBy('id');
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(PageRevision::class)->orderByDesc('version');
    }

    public function publishedRevision(): BelongsTo
    {
        return $this->belongsTo(PageRevision::class, 'published_revision_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', PageStatus::Published)->whereNotNull('published_revision_id');
    }

    public function isPublished(): bool
    {
        return $this->status === PageStatus::Published && $this->published_revision_id !== null;
    }

    public function isHome(): bool
    {
        return $this->type === PageType::Home;
    }

    public function publicUrl(): string
    {
        return $this->isHome() ? route('home') : route('page.show', $this->slug);
    }

    /** Apakah draft berbeda dari versi published terakhir (dibandingkan lewat lock_version snapshot). */
    public function hasUnpublishedChanges(): bool
    {
        if (! $this->published_revision_id) {
            return true;
        }

        return (int) ($this->publishedRevision?->snapshot['lock_version'] ?? -1) !== $this->lock_version;
    }
}
