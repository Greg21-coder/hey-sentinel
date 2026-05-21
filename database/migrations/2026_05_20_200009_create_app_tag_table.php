<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('ai_tags')->cascadeOnDelete();
            $table->unsignedInteger('mention_count')->default(1);
            $table->timestamps();

            $table->unique(['app_id', 'tag_id'], 'uniq_app_tag');
            $table->index('tag_id', 'idx_app_tag_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_tag');
    }
};
