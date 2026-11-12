<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('company_name', 180);
            $table->string('contact_name', 150);
            $table->string('phone', 30);
            $table->string('email', 180)->nullable();
            $table->string('company_address', 500)->nullable();
            $table->foreignId('industry_solution_id')->nullable()->constrained('industry_solutions')->nullOnDelete();
            $table->string('industry', 120)->nullable();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('bandwidth_requirement', 120)->nullable();
            $table->unsignedInteger('locations_count')->nullable();
            $table->text('message')->nullable();
            $table->string('type', 20)->default('contact'); // contact, rfq, consultation
            $table->string('source', 30)->default('website_form');
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->json('utm')->nullable();
            $table->string('landing_page')->nullable();
            $table->string('status', 30)->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('follow_up_at')->nullable();
            $table->string('lost_reason')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('consent_at')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->softDeletes();
            $table->timestamps();
            $table->index('status');
            $table->index(['assigned_to', 'status']);
            $table->index('follow_up_at');
            $table->index('created_at');
            $table->index('company_name');
        });

        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('lead_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('description')->nullable();
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['lead_id', 'created_at']);
        });

        Schema::create('lead_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at');
            $table->timestamp('unassigned_at')->nullable();
            $table->index(['lead_id', 'assigned_at']);
        });

        Schema::create('whatsapp_clicks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained('campaigns')->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->string('source_path')->nullable();
            $table->string('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        foreach (['whatsapp_clicks', 'lead_assignments', 'lead_activities', 'lead_notes', 'leads'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
