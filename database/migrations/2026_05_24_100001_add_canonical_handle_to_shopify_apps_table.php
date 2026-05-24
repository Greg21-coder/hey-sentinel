<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            // Set when apps.shopify.com/{handle} 301-redirects to an opaque hash
            // (e.g. Yotpo: 'yotpo' -> '659062da3dcade1068da9e28c3d120c5'). Review
            // scraping must use this instead of shopify_app_handle, since the
            // /{handle}/reviews path 404s for the public handle but works at the hash.
            $table->string('canonical_handle')->nullable()->after('shopify_app_handle');
            $table->unique('canonical_handle');
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropUnique(['canonical_handle']);
            $table->dropColumn('canonical_handle');
        });
    }
};
