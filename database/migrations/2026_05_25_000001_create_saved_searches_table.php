<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_searches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->json('filters');
            $table->boolean('notify_on_new')->default(false);
            $table->timestamp('last_viewed_at')->nullable();
            $table->timestamps();
            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_searches');
    }
};
