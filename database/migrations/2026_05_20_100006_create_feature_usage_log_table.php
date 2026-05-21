<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usage_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->integer('delta');
            $table->timestamp('occurred_at')->useCurrent();

            $table->index(['account_id', 'feature_key', 'occurred_at'], 'idx_feature_usage_acct_feat_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage_log');
    }
};
