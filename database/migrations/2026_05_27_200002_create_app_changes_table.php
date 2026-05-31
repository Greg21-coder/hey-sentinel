<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_changes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shopify_app_id')->constrained('shopify_apps')->cascadeOnDelete();
            $table->string('field', 50);
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->timestamp('detected_at');

            $table->index(['shopify_app_id', 'detected_at']);
            $table->index(['field', 'detected_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_changes');
    }
};
