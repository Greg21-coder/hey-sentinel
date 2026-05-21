<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Use raw SQL because Schema builder doesn't support PARTITION BY RANGE.
        DB::statement(<<<'SQL'
            CREATE TABLE store_reviews (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                shopify_app_id BIGINT UNSIGNED NOT NULL,
                shopify_store_id BIGINT UNSIGNED NULL,
                reviewer_name VARCHAR(255) NOT NULL,
                rating TINYINT UNSIGNED NOT NULL,
                review_text TEXT NOT NULL,
                review_text_hash CHAR(64) NOT NULL,
                language_code CHAR(5) NULL,
                ai_status VARCHAR(20) NOT NULL DEFAULT 'pending',
                ai_sentiment VARCHAR(50) NULL,
                ai_processed_at DATETIME NULL,
                published_at DATETIME NOT NULL,
                created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id, published_at),
                UNIQUE KEY uniq_app_text_hash (shopify_app_id, review_text_hash, published_at),
                KEY idx_app_published (shopify_app_id, published_at DESC),
                KEY idx_app_rating (shopify_app_id, rating),
                KEY idx_app_aistatus (shopify_app_id, ai_status),
                KEY idx_aistatus_pub (ai_status, published_at),
                KEY idx_store (shopify_store_id)
                -- FULLTEXT not supported on InnoDB partitioned tables.
                -- Phase 5 will add ngram/Meilisearch for cross-partition search.
                -- FOREIGN KEYs to shopify_apps / shopify_stores not allowed on
                -- partitioned InnoDB tables; integrity enforced at the model
                -- layer via Eloquent relations and pre-insert validation.
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            PARTITION BY RANGE (YEAR(published_at)) (
                PARTITION p2019 VALUES LESS THAN (2020),
                PARTITION p2020 VALUES LESS THAN (2021),
                PARTITION p2021 VALUES LESS THAN (2022),
                PARTITION p2022 VALUES LESS THAN (2023),
                PARTITION p2023 VALUES LESS THAN (2024),
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026),
                PARTITION p2026 VALUES LESS THAN (2027),
                PARTITION p2027 VALUES LESS THAN (2028),
                PARTITION pmax VALUES LESS THAN MAXVALUE
            )
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('store_reviews');
    }
};
