<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\Orderable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProductCategory extends Model
{
    use LogsActivity, Orderable;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_visible'];

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_category_pivot');
    }
}
