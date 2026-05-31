<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_change_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_change_id')->constrained('app_changes')->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['account_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_change_notifications');
    }
};
