<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add indexes that back the hot query paths:
     *  - (user_id, variant_sku): the SKU upsert done on every CSV import and
     *    Shopify re-sync. Without it the lookup scans the user's rows.
     *  - shopify_status: the dashboard's real-time status polling filters on it.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['user_id', 'variant_sku'], 'products_user_id_variant_sku_index');
            $table->index('shopify_status', 'products_shopify_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_user_id_variant_sku_index');
            $table->dropIndex('products_shopify_status_index');
        });
    }
};
