<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->timestamp('unlisted_at')->nullable()->after('ai_summary_model');
        });
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->timestamp('unlisted_at')->nullable()->after('scraping_status');
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn('unlisted_at');
        });
        Schema::table('shopify_stores', function (Blueprint $table) {
            $table->dropColumn('unlisted_at');
        });
    }
};
