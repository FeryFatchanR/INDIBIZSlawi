<?php

namespace App\Models;

use App\Enums\ProductStatus;
use App\Models\Concerns\HasUuid;
use App\Models\Concerns\LogsActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, HasUuid, LogsActivity, SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'icon', 'short_description', 'description', 'featured_image_id', 'bandwidth_label', 'sla',
        'benefits', 'target_customers', 'price_label', 'show_price', 'cta_label', 'cta_url', 'status',
        'published_at', 'is_featured', 'sort_order', 'meta_title', 'meta_description', 'og_image_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProductStatus::class,
            'benefits' => 'array',
            'target_customers' => 'array',
            'show_price' => 'boolean',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $product) {
            if ($product->status === ProductStatus::Published && ! $product->published_at) {
                $product->published_at = now();
            }
        });
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ProductStatus::Published)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->orderBy('sort_order')->orderBy('id');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProductCategory::class, 'product_category_pivot');
    }

    public function industrySolutions(): BelongsToMany
    {
        return $this->belongsToMany(IndustrySolution::class);
    }

    public function specifications(): HasMany
    {
        return $this->hasMany(ProductSpecification::class)->orderBy('sort_order');
    }

    public function galleries(): HasMany
    {
        return $this->hasMany(ProductGallery::class)->orderBy('sort_order');
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    public function featuredImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'featured_image_id');
    }

    public function ogImage(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_image_id');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function isPublished(): bool
    {
        return $this->status === ProductStatus::Published;
    }

    public function publicUrl(): string
    {
        return route('products.show', $this->slug);
    }
}
