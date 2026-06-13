<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncProductToShopify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = 10;

    protected $product;

    /**
     * Create a new job instance.
     */
    public function __construct(Product $product)
    {
        $this->product = $product;
    }

    /**
     * Execute the job.
     */
    public function handle(ShopifyService $shopify): void
    {
        // Refresh in case the row changed since dispatch.
        $product = $this->product->fresh();
        if (!$product) {
            return;
        }

        $shopify->syncAndRecord($product);
    }

    /**
     * Handle a job failure (after all retries are exhausted).
     */
    public function failed(\Throwable $exception): void
    {
        $product = $this->product->fresh();
        if ($product) {
            $product->update([
                'shopify_status' => 'failed',
                'shopify_error' => $exception->getMessage(),
            ]);
        }
    }
}
