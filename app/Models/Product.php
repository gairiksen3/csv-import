<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'handle',
        'title',
        'body_html',
        'vendor',
        'product_type',
        'tags',
        'published',
        'variant_sku',
        'variant_price',
        'variant_compare_at_price',
        'variant_requires_shipping',
        'variant_taxable',
        'variant_inventory_tracker',
        'variant_inventory_qty',
        'variant_inventory_policy',
        'variant_fulfillment_service',
        'variant_weight',
        'variant_weight_unit',
        'image_src',
        'image_position',
        'image_alt_text',
        'user_id',
        'shopify_status',
        'shopify_product_id',
        'shopify_error',
        'shopify_synced_at',
    ];

    protected $casts = [
        'shopify_synced_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
