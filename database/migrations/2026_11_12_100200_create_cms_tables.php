<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title');
            $table->string('slug', 160)->unique();
            $table->string('type', 20)->default('page'); // page, home, global
            $table->string('status', 20)->default('draft'); // draft, published, unpublished
            $table->unsignedBigInteger('published_revision_id')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('canonical_url')->nullable();
            $table->foreignId('og_image_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->boolean('noindex')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index(['status', 'type']);
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->uuid('parent_uuid')->nullable();
            $table->string('type', 40);
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->json('content')->nullable();
            $table->json('design')->nullable();
            $table->json('responsive')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['page_id', 'parent_uuid', 'sort_order']);
        });

        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('pages')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('kind', 20); // draft, publish, restore
            $table->string('note')->nullable();
            $table->json('snapshot');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
            $table->unique(['page_id', 'version']);
            $table->index(['page_id', 'kind']);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreign('published_revision_id')->references('id')->on('page_revisions')->nullOnDelete();
        });

        Schema::create('global_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50);
            $table->string('key', 80);
            $table->json('value')->nullable();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign(['published_revision_id']);
        });
        Schema::dropIfExists('global_settings');
        Schema::dropIfExists('page_revisions');
        Schema::dropIfExists('page_sections');
        Schema::dropIfExists('pages');
    }
};
