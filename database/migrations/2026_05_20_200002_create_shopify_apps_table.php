<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_apps', function (Blueprint $table) {
            $table->id();
            $table->string('shopify_app_handle', 255)->unique();
            $table->string('name', 255);
            $table->string('developer_name', 255);
            $table->string('developer_url', 500)->nullable();
            $table->foreignId('category_id')->nullable()->constrained('shopify_app_categories')->nullOnDelete();
            $table->mediumText('description')->nullable();
            $table->text('pricing_raw')->nullable();
            $table->json('pricing_structured')->nullable();
            $table->decimal('pricing_min_usd', 10, 2)->nullable();
            $table->boolean('pricing_has_free')->default(false);
            $table->string('avatar_url', 500)->nullable();
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->unsignedInteger('total_installs_estimate')->nullable();
            $table->string('scraping_status', 20)->default('pending');
            $table->text('scraping_error')->nullable();
            $table->timestamp('last_scraped_at')->nullable();
            $table->timestamp('ai_processed_at')->nullable();
            $table->timestamps();

            $table->index(['scraping_status', 'last_scraped_at'], 'idx_apps_status_scraped');
            $table->index(['category_id', 'average_rating'], 'idx_apps_category_rating');
            $table->index(['pricing_min_usd', 'pricing_has_free'], 'idx_apps_pricing');
            $table->fullText(['name', 'description'], 'idx_apps_search');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_apps');
    }
};
