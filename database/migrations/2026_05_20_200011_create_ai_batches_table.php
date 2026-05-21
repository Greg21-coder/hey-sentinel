<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_batches', function (Blueprint $table) {
            $table->id();
            $table->string('provider', 40)->default('anthropic');
            $table->string('batch_id', 120)->unique();
            $table->string('status', 30)->default('submitted'); // submitted|in_progress|completed|failed
            $table->unsignedInteger('request_count')->default(0);
            $table->decimal('cost_usd_estimate', 10, 4)->nullable();
            $table->string('prompt_version', 40)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('status', 'idx_ai_batches_status');
            $table->index('submitted_at', 'idx_ai_batches_submitted');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_batches');
    }
};
