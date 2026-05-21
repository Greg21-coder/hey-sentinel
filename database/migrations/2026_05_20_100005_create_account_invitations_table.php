<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->cascadeOnDelete();
            $table->string('email');
            $table->string('role', 20);
            $table->char('token', 64)->unique();
            $table->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('email', 'idx_account_invitations_email');
            $table->index(['account_id', 'accepted_at'], 'idx_account_invitations_acct_acpt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_invitations');
    }
};
