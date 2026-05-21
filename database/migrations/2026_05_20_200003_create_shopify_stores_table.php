<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_stores', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255)->unique();
            $table->string('store_name', 255)->nullable();
            $table->char('country_code', 2)->nullable();
            $table->char('language_code', 5)->nullable();
            $table->string('platform_tier', 40)->nullable();
            $table->unsignedInteger('estimated_monthly_visits')->nullable();
            $table->unsignedBigInteger('estimated_monthly_sales_usd')->nullable();
            $table->unsignedInteger('employees_estimate')->nullable();
            $table->string('theme_name', 100)->nullable();
            $table->unsignedSmallInteger('apps_installed_count')->nullable();
            $table->string('storeleads_id', 100)->nullable()->unique();
            $table->char('storeleads_payload_hash', 64)->nullable();
            $table->timestamp('storeleads_synced_at')->nullable();
            $table->string('scraping_status', 20)->default('pending');
            $table->timestamps();

            $table->index(['country_code', 'platform_tier'], 'idx_stores_country_tier');
            $table->index('estimated_monthly_sales_usd', 'idx_stores_revenue');
            $table->index('estimated_monthly_visits', 'idx_stores_visits');
            $table->index('storeleads_synced_at', 'idx_stores_sync');
            $table->index('scraping_status', 'idx_stores_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_stores');
    }
};
