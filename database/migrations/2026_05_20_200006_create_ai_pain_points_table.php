<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_pain_points', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->string('name', 120);
            $table->string('category', 50);
            $table->timestamps();

            $table->index('category', 'idx_pain_points_category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_pain_points');
    }
};
