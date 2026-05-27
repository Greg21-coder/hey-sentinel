<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovery_runs', function (Blueprint $table) {
            $table->id();
            $table->string('source', 20)->default('sitemap');
            $table->string('status', 20)->default('pending');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('apps_found')->default(0);
            $table->unsignedInteger('apps_new')->default(0);
            $table->unsignedInteger('apps_existing')->default(0);
            $table->unsignedInteger('apps_limit')->nullable();
            $table->text('error_message')->nullable();
            $table->string('triggered_by', 10);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovery_runs');
    }
};
