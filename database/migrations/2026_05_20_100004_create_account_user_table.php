<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('role', 20);
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('invitation_accepted_at')->nullable();
            $table->timestamps();

            $table->unique(['account_id', 'user_id'], 'uniq_account_user_account_user');
            $table->index(['user_id', 'role'], 'idx_account_user_user_role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_user');
    }
};
