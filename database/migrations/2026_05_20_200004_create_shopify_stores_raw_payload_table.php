<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_stores_raw_payload', function (Blueprint $table) {
            $table->foreignId('shopify_store_id')->primary()->constrained('shopify_stores')->cascadeOnDelete();
            $table->json('payload');
            $table->timestamp('imported_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_stores_raw_payload');
    }
};
