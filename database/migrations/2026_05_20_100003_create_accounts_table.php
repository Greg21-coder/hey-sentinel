<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('status', 20)->default('trial');
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            $table->string('billing_cycle', 10)->default('none');
            $table->timestamp('trial_started_at')->nullable();
            $table->timestamp('free_trial_ends_at')->nullable();
            $table->timestamp('current_period_starts_at')->nullable();
            $table->timestamp('current_period_ends_at')->nullable();
            $table->string('stripe_customer_id', 100)->nullable()->unique();
            $table->foreignId('owner_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'plan_id'], 'idx_accounts_status_plan');
            $table->index('free_trial_ends_at', 'idx_accounts_trial_end');
            $table->index('current_period_ends_at', 'idx_accounts_period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
