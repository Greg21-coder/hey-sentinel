<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->json('features_json')->nullable()->after('ai_summary_model');
            $table->timestamp('features_extracted_at')->nullable()->after('features_json');
            $table->string('features_extraction_model', 64)->nullable()->after('features_extracted_at');
        });
    }

    public function down(): void
    {
        Schema::table('shopify_apps', function (Blueprint $table) {
            $table->dropColumn(['features_json', 'features_extracted_at', 'features_extraction_model']);
        });
    }
};
