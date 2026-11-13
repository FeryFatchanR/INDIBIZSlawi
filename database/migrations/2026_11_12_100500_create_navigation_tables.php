<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menus', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name', 120);
            $table->timestamps();
        });

        Schema::create('navigation_menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained('navigation_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('navigation_menu_items')->cascadeOnDelete();
            $table->string('label', 120);
            $table->string('link_type', 20)->default('url'); // url, page, product, solution
            $table->foreignId('page_id')->nullable()->constrained('pages')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('industry_solution_id')->nullable()->constrained('industry_solutions')->nullOnDelete();
            $table->string('url')->nullable();
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->timestamps();
            $table->index(['navigation_menu_id', 'parent_id', 'sort_order'], 'nav_items_menu_parent_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menu_items');
        Schema::dropIfExists('navigation_menus');
    }
};
