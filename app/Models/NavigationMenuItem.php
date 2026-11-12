<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationMenuItem extends Model
{
    protected $fillable = [
        'navigation_menu_id', 'parent_id', 'label', 'link_type', 'page_id', 'product_id', 'industry_solution_id',
        'url', 'open_in_new_tab', 'sort_order', 'is_visible',
    ];

    protected function casts(): array
    {
        return ['open_in_new_tab' => 'boolean', 'is_visible' => 'boolean', 'sort_order' => 'integer'];
    }

    public function menu(): BelongsTo
    {
        return $this->belongsTo(NavigationMenu::class, 'navigation_menu_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->where('is_visible', true)->orderBy('sort_order');
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function industrySolution(): BelongsTo
    {
        return $this->belongsTo(IndustrySolution::class);
    }

    /** URL akhir; null jika target tidak lagi dipublikasikan. */
    public function resolvedUrl(): ?string
    {
        return match ($this->link_type) {
            'page' => $this->page?->isPublished() ? $this->page->publicUrl() : null,
            'product' => $this->product?->isPublished() ? $this->product->publicUrl() : null,
            'solution' => $this->industrySolution?->is_visible ? $this->industrySolution->publicUrl() : null,
            default => $this->url,
        };
    }
}
