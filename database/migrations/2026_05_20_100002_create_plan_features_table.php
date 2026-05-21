<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_key', 80);
            $table->string('feature_value', 255);
            $table->string('value_type', 20);
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key'], 'uniq_plan_features_plan_feature');
            $table->index('feature_key', 'idx_plan_features_feature_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
