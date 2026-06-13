<?php

namespace App\Jobs;

use App\Models\Product;
use App\Notifications\ImportNotification;
use App\Services\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncImportProductsToShopify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    /** @var int[] */
    protected array $productIds;

    /**
     * @param int[] $productIds Products (already in the DB) to push to Shopify.
     */
    public function __construct(array $productIds)
    {
        $this->productIds = $productIds;
    }

    /**
     * Execute the job: sync each product to Shopify one at a time.
     *
     * The Shopify Admin API creates/updates products one per request, so the
     * per-product loop lives here in a single background job rather than in the
     * CSV parsing loop (which now only writes to the database).
     */
    public function handle(ShopifyService $shopify): void
    {
        $log = Log::channel('import');

        if (!$shopify->isConfigured()) {
            $log->warning('Shopify sync skipped: integration not configured', [
                'product_count' => count($this->productIds),
            ]);

            return;
        }

        $log->info('Shopify batch sync started', ['product_count' => count($this->productIds)]);

        $succeeded = 0;
        $failed = 0;
        $user = null;

        // Preserve import order; chunk to keep memory flat for large imports.
        Product::whereIn('id', $this->productIds)
            ->orderBy('id')
            ->chunkById(50, function ($products) use ($shopify, &$succeeded, &$failed, &$user) {
                foreach ($products as $product) {
                    $user ??= $product->user;
                    $shopify->syncAndRecord($product) ? $succeeded++ : $failed++;
                }
            });

        $log->info('Shopify batch sync finished', [
            'succeeded' => $succeeded,
            'failed' => $failed,
        ]);

        // Notify the user if any product failed to sync to Shopify.
        if ($failed > 0 && $user) {
            $user->notify(new ImportNotification(
                'error',
                'Shopify sync had failures',
                "{$failed} product(s) failed to sync to Shopify, {$succeeded} succeeded. Open a product to see the error.",
                route('products.user-list')
            ));
        }
    }
}
