<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('app_comparison_summaries', function (Blueprint $table) {
            $table->string('prompt_version', 32)->change();
        });
    }

    public function down(): void
    {
        Schema::table('app_comparison_summaries', function (Blueprint $table) {
            $table->string('prompt_version', 16)->change();
        });
    }
};
