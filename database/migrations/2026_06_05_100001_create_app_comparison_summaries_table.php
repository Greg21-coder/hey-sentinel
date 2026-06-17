<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_comparison_summaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('mine_shopify_app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->char('competitor_ids_hash', 40);
            $table->json('competitor_ids');
            $table->longText('summary');
            $table->foreignId('winner_shopify_app_id')->nullable()->constrained('shopify_apps')->nullOnDelete();
            $table->text('winner_reasoning')->nullable();
            $table->json('per_metric_comments')->nullable();
            $table->string('model', 64);
            $table->string('prompt_version', 32);
            $table->timestamp('generated_at');
            $table->timestamps();

            $table->unique(['account_id', 'mine_shopify_app_id', 'competitor_ids_hash'], 'acs_account_mine_hash_unique');
            $table->index(['account_id', 'mine_shopify_app_id'], 'acs_account_mine_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_comparison_summaries');
    }
};
