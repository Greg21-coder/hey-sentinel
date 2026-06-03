<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->timestamp('reviews_sync_started_at')->nullable()->after('last_scraped_at');
        });

        DB::table('shopify_apps')
            ->where('scraping_status', 'scraped')
            ->whereNotNull('last_scraped_at')
            ->update(['reviews_sync_started_at' => DB::raw('last_scraped_at')]);
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn('reviews_sync_started_at');
        });
    }
};
