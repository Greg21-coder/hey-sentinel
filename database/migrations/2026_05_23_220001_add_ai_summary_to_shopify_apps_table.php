<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->text('ai_summary')->nullable()->after('ai_processed_at');
            $table->timestamp('ai_summary_at')->nullable()->after('ai_summary');
            $table->string('ai_summary_model', 80)->nullable()->after('ai_summary_at');
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn(['ai_summary', 'ai_summary_at', 'ai_summary_model']);
        });
    }
};
