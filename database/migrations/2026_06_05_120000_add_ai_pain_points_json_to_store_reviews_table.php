<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // store_reviews is a partitioned table — Schema builder ALTER TABLE
        // works fine here since we're just adding a nullable column.
        DB::statement('ALTER TABLE store_reviews ADD COLUMN ai_pain_points_json TEXT NULL AFTER ai_sentiment');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE store_reviews DROP COLUMN ai_pain_points_json');
    }
};
