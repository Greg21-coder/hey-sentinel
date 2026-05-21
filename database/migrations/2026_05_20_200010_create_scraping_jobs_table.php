<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scraping_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('target_type', 40);          // 'app' | 'app_reviews' | 'store' | 'storeleads_batch'
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('url', 1000)->nullable();
            $table->string('status', 20)->default('pending');  // pending|running|completed|failed|blocked
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['target_type', 'target_id'], 'idx_scraping_target');
            $table->index(['status', 'scheduled_for'], 'idx_scraping_status_sched');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scraping_jobs');
    }
};
