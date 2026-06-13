<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // pending | processing | successful | failed
            $table->string('shopify_status')->default('pending')->after('image_alt_text');
            $table->string('shopify_product_id')->nullable()->after('shopify_status');
            $table->text('shopify_error')->nullable()->after('shopify_product_id');
            $table->timestamp('shopify_synced_at')->nullable()->after('shopify_error');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'shopify_status',
                'shopify_product_id',
                'shopify_error',
                'shopify_synced_at',
            ]);
        });
    }
};
