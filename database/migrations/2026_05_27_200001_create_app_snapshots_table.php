<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('developer_name', 255);
            $table->string('description_hash', 64)->nullable();
            $table->string('pricing_raw', 255)->nullable();
            $table->decimal('pricing_min_usd', 8, 2)->nullable();
            $table->boolean('pricing_has_free')->default(false);
            $table->decimal('average_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_reviews')->default(0);
            $table->string('category_name', 100)->nullable();
            $table->string('avatar_url', 500)->nullable();
            $table->timestamp('snapshot_at');

            $table->index(['shopify_app_id', 'snapshot_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_snapshots');
    }
};
