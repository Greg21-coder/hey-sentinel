<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_pain_point', function (Blueprint $table) {
            $table->id();
            // FK to store_reviews is not declared here because store_reviews
            // uses composite PK (id, published_at) for partitioning; MySQL
            // does not allow a FK against a non-unique single column.
            $table->unsignedBigInteger('review_id');
            $table->foreignId('pain_point_id')->constrained('ai_pain_points')->cascadeOnDelete();
            $table->string('severity', 20)->default('medium');
            $table->decimal('confidence', 4, 3)->default(1.000);
            $table->timestamps();

            $table->unique(['review_id', 'pain_point_id'], 'uniq_review_pain_point');
            $table->index('review_id', 'idx_rpp_review');
            $table->index('pain_point_id', 'idx_rpp_pain_point');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_pain_point');
    }
};
