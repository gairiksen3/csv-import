<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    protected string $storeDomain;
    protected string $accessToken;
    protected ?string $collectionId;
    protected string $apiVersion;

    protected ?string $primaryLocationId = null;
    protected bool $locationFetched = false;

    public function __construct()
    {
        $this->storeDomain = (string) config('services.shopify.store_domain');
        $this->accessToken = (string) config('services.shopify.access_token');
        $this->collectionId = config('services.shopify.collection_id');
        $this->apiVersion = (string) config('services.shopify.api_version', '2024-10');
    }

    /**
     * Whether the Shopify integration is configured well enough to attempt a sync.
     */
    public function isConfigured(): bool
    {
        return $this->storeDomain !== '' && $this->accessToken !== '';
    }

    /**
     * Upsert a product in Shopify, matched by its variant SKU.
     *
     * If a variant with the same SKU already exists in the store, the existing
     * product/variant is updated. Otherwise a new product is created. Uses the
     * Shopify GraphQL Admin API throughout.
     *
     * @return string The Shopify product id (numeric, parsed from the GID).
     * @throws \RuntimeException on any failure.
     */
    public function syncProduct(Product $product): string
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Shopify is not configured. Set SHOPIFY_STORE_DOMAIN and SHOPIFY_ACCESS_TOKEN in your .env file.');
        }

        $sku = trim((string) $product->variant_sku);
        $existing = $sku !== '' ? $this->findVariantBySku($sku) : null;

        if ($existing) {
            // Product already in Shopify -> update product + variant details.
            $productGid = $existing['productId'];
            $variantGid = $existing['variantId'];
            $inventoryItemGid = $existing['inventoryItemId'];

            $this->updateProduct($productGid, $product);
        } else {
            // Not in Shopify yet -> create it.
            $created = $this->createProduct($product);
            $productGid = $created['productId'];
            $variantGid = $created['variantId'];
            $inventoryItemGid = $created['inventoryItemId'];

            $this->attachImage($productGid, $product);
        }

        // Variant details (price, sku, weight, etc.) for both create and update paths.
        $this->updateVariant($productGid, $variantGid, $product);

        // Inventory quantity is best-effort: it depends on the item being tracked
        // and activated at a location, so a failure here must not fail the sync.
        if ($inventoryItemGid && $product->variant_inventory_qty !== null) {
            try {
                $this->setInventoryQuantity($inventoryItemGid, (int) $product->variant_inventory_qty);
            } catch (\Throwable $e) {
                Log::warning('Shopify inventory quantity update skipped for product ' . $product->id . ': ' . $e->getMessage());
            }
        }

        // Ensure the product is in the configured collection (idempotent).
        if (!empty($this->collectionId)) {
            $this->addToCollection($productGid);
        }

        return $this->gidToId($productGid);
    }

    /**
     * Sync a product to Shopify and persist the resulting status on the model.
     *
     * Sets the product to "processing", then "successful" (storing the Shopify
     * id) or "failed" (storing the error). Never throws — returns whether the
     * sync succeeded so callers can keep processing the rest of a batch.
     */
    public function syncAndRecord(Product $product): bool
    {
        $log = Log::channel('import');
        $product->update(['shopify_status' => 'processing']);

        try {
            $shopifyProductId = $this->syncProduct($product);

            $product->update([
                'shopify_status' => 'successful',
                'shopify_product_id' => $shopifyProductId,
                'shopify_error' => null,
                'shopify_synced_at' => now(),
            ]);

            $log->info('Shopify sync succeeded', [
                'product_id' => $product->id,
                'sku' => $product->variant_sku,
                'shopify_product_id' => $shopifyProductId,
            ]);

            return true;
        } catch (\Throwable $e) {
            $product->update([
                'shopify_status' => 'failed',
                'shopify_error' => $e->getMessage(),
            ]);

            $log->error('Shopify sync failed', [
                'product_id' => $product->id,
                'sku' => $product->variant_sku,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Find an existing variant (and its product) by SKU.
     *
     * @return array{productId:string,variantId:string,inventoryItemId:?string}|null
     */
    protected function findVariantBySku(string $sku): ?array
    {
        $query = <<<'GRAPHQL'
            query findVariant($q: String!) {
                productVariants(first: 1, query: $q) {
                    edges {
                        node {
                            id
                            inventoryItem { id }
                            product { id }
                        }
                    }
                }
            }
        GRAPHQL;

        $data = $this->graphql($query, ['q' => 'sku:"' . $this->escapeSearch($sku) . '"']);

        $node = data_get($data, 'productVariants.edges.0.node');
        if (!$node) {
            return null;
        }

        return [
            'productId' => data_get($node, 'product.id'),
            'variantId' => data_get($node, 'id'),
            'inventoryItemId' => data_get($node, 'inventoryItem.id'),
        ];
    }

    /**
     * Create a new product (with its default variant).
     *
     * @return array{productId:string,variantId:string,inventoryItemId:?string}
     */
    protected function createProduct(Product $product): array
    {
        $mutation = <<<'GRAPHQL'
            mutation createProduct($input: ProductInput!) {
                productCreate(input: $input) {
                    product {
                        id
                        variants(first: 1) {
                            nodes { id inventoryItem { id } }
                        }
                    }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $data = $this->graphql($mutation, ['input' => $this->buildProductInput($product)]);
        $this->assertNoUserErrors($data, 'productCreate');

        $variant = data_get($data, 'productCreate.product.variants.nodes.0');

        return [
            'productId' => data_get($data, 'productCreate.product.id'),
            'variantId' => data_get($variant, 'id'),
            'inventoryItemId' => data_get($variant, 'inventoryItem.id'),
        ];
    }

    /**
     * Update an existing product's top-level details.
     */
    protected function updateProduct(string $productGid, Product $product): void
    {
        $mutation = <<<'GRAPHQL'
            mutation updateProduct($input: ProductInput!) {
                productUpdate(input: $input) {
                    product { id }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $input = $this->buildProductInput($product);
        $input['id'] = $productGid;

        $data = $this->graphql($mutation, ['input' => $input]);
        $this->assertNoUserErrors($data, 'productUpdate');
    }

    /**
     * Update the variant's details (price, sku, weight, taxability, etc.).
     */
    protected function updateVariant(string $productGid, string $variantGid, Product $product): void
    {
        $mutation = <<<'GRAPHQL'
            mutation bulkUpdate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
                productVariantsBulkUpdate(productId: $productId, variants: $variants) {
                    productVariants { id }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $inventoryItem = array_filter([
            'sku' => $product->variant_sku ?: null,
            'tracked' => $product->variant_inventory_tracker ? true : false,
            'requiresShipping' => (bool) $product->variant_requires_shipping,
            'measurement' => $product->variant_weight !== null ? [
                'weight' => [
                    'value' => (float) $product->variant_weight,
                    'unit' => $this->weightUnit($product->variant_weight_unit),
                ],
            ] : null,
        ], fn ($v) => $v !== null);

        $variant = array_filter([
            'id' => $variantGid,
            'price' => $product->variant_price !== null ? (string) $product->variant_price : null,
            'compareAtPrice' => $product->variant_compare_at_price !== null ? (string) $product->variant_compare_at_price : null,
            'taxable' => (bool) $product->variant_taxable,
            'inventoryPolicy' => $this->inventoryPolicy($product->variant_inventory_policy),
            'inventoryItem' => $inventoryItem ?: null,
        ], fn ($v) => $v !== null);

        $data = $this->graphql($mutation, [
            'productId' => $productGid,
            'variants' => [$variant],
        ]);
        $this->assertNoUserErrors($data, 'productVariantsBulkUpdate');
    }

    /**
     * Attach an image to a product (used on create only, to avoid duplicates).
     */
    protected function attachImage(string $productGid, Product $product): void
    {
        if (!$product->image_src) {
            return;
        }

        $mutation = <<<'GRAPHQL'
            mutation createMedia($productId: ID!, $media: [CreateMediaInput!]!) {
                productCreateMedia(productId: $productId, media: $media) {
                    media { ... on MediaImage { id } }
                    mediaUserErrors { field message }
                }
            }
        GRAPHQL;

        $media = array_filter([
            'originalSource' => $product->image_src,
            'mediaContentType' => 'IMAGE',
            'alt' => $product->image_alt_text ?: null,
        ], fn ($v) => $v !== null);

        $data = $this->graphql($mutation, [
            'productId' => $productGid,
            'media' => [$media],
        ]);

        $errors = data_get($data, 'productCreateMedia.mediaUserErrors', []);
        if (!empty($errors)) {
            // Image issues should not fail the whole sync.
            Log::warning('Shopify image attach issue for product ' . $product->id . ': ' . json_encode($errors));
        }
    }

    /**
     * Best-effort: set the available inventory quantity at the primary location.
     *
     * @throws \RuntimeException on failure (caught by the caller).
     */
    protected function setInventoryQuantity(string $inventoryItemGid, int $quantity): void
    {
        $locationId = $this->primaryLocationId();
        if (!$locationId) {
            throw new \RuntimeException('No Shopify location available to set inventory.');
        }

        $mutation = <<<'GRAPHQL'
            mutation setQty($input: InventorySetQuantitiesInput!) {
                inventorySetQuantities(input: $input) {
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $data = $this->graphql($mutation, [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'ignoreCompareQuantity' => true,
                'quantities' => [[
                    'inventoryItemId' => $inventoryItemGid,
                    'locationId' => $locationId,
                    'quantity' => $quantity,
                ]],
            ],
        ]);
        $this->assertNoUserErrors($data, 'inventorySetQuantities');
    }

    /**
     * Add a product to the configured collection.
     */
    protected function addToCollection(string $productGid): void
    {
        $mutation = <<<'GRAPHQL'
            mutation addToCollection($id: ID!, $productIds: [ID!]!) {
                collectionAddProducts(id: $id, productIds: $productIds) {
                    collection { id }
                    userErrors { field message }
                }
            }
        GRAPHQL;

        $data = $this->graphql($mutation, [
            'id' => 'gid://shopify/Collection/' . $this->collectionId,
            'productIds' => [$productGid],
        ]);
        $this->assertNoUserErrors($data, 'collectionAddProducts');
    }

    /**
     * Build the shared ProductInput payload from a local product.
     */
    protected function buildProductInput(Product $product): array
    {
        $tags = collect(explode(',', (string) $product->tags))
            ->map(fn ($t) => trim($t))
            ->filter()
            ->values()
            ->all();

        return array_filter([
            'title' => $product->title,
            'descriptionHtml' => $product->body_html,
            'vendor' => $product->vendor,
            'productType' => $product->product_type,
            'handle' => $product->handle ?: null,
            'tags' => $tags ?: null,
            'status' => $product->published ? 'ACTIVE' : 'DRAFT',
        ], fn ($v) => $v !== null && $v !== '');
    }

    protected function primaryLocationId(): ?string
    {
        if ($this->locationFetched) {
            return $this->primaryLocationId;
        }

        $this->locationFetched = true;

        $data = $this->graphql('query { locations(first: 1) { nodes { id } } }');
        $this->primaryLocationId = data_get($data, 'locations.nodes.0.id');

        return $this->primaryLocationId;
    }

    /**
     * Execute a GraphQL request and return the `data` payload.
     *
     * @throws \RuntimeException on transport or GraphQL-level errors.
     */
    protected function graphql(string $query, array $variables = []): array
    {
        $response = Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->acceptJson()->timeout(30)->post(
            "https://{$this->storeDomain}/admin/api/{$this->apiVersion}/graphql.json",
            ['query' => $query, 'variables' => (object) $variables]
        );

        if (!$response->successful()) {
            throw new \RuntimeException('Shopify GraphQL HTTP ' . $response->status() . ': ' . $response->body());
        }

        $json = $response->json();

        if (!empty($json['errors'])) {
            throw new \RuntimeException('Shopify GraphQL errors: ' . json_encode($json['errors']));
        }

        return $json['data'] ?? [];
    }

    /**
     * Throw if a mutation returned userErrors.
     */
    protected function assertNoUserErrors(array $data, string $root): void
    {
        $errors = data_get($data, "$root.userErrors", []);
        if (!empty($errors)) {
            throw new \RuntimeException(ucfirst($root) . ' failed: ' . json_encode($errors));
        }
    }

    protected function weightUnit(?string $unit): string
    {
        return match (strtolower(trim((string) $unit))) {
            'g', 'grams', 'gram' => 'GRAMS',
            'lb', 'lbs', 'pound', 'pounds' => 'POUNDS',
            'oz', 'ounce', 'ounces' => 'OUNCES',
            default => 'KILOGRAMS',
        };
    }

    protected function inventoryPolicy(?string $policy): string
    {
        return strtolower(trim((string) $policy)) === 'continue' ? 'CONTINUE' : 'DENY';
    }

    protected function gidToId(string $gid): string
    {
        $parts = explode('/', $gid);
        return end($parts) ?: $gid;
    }

    protected function escapeSearch(string $value): string
    {
        return str_replace(['\\', '"'], ['\\\\', '\\"'], $value);
    }
}
