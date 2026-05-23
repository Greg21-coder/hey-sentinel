<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('account_followed_apps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shopify_app_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 20)->default('competitor');
            $table->timestamp('followed_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'shopify_app_id']);
            $table->index(['account_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_followed_apps');
    }
};
