<?php

namespace App\Http\Controllers;

use App\Jobs\SyncProductToShopify;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Show products list
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if ($user->role === 'admin') {
            // Admin sees all products from all users
            $products = Product::with('user')
                ->latest('id')
                ->paginate(15);

            return view('products.admin-list', compact('products'));
        } else {
            // User sees only their own products
            $products = Product::where('user_id', $user->id)
                ->latest('id')
                ->paginate(15);

            return view('products.user-list', compact('products'));
        }
    }

    /**
     * Get product details via AJAX
     */
    public function show($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Verify user owns this product or is admin
            if (auth()->user()->role !== 'admin' && $product->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'product' => $product,
                'user' => $product->user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }
    }

    /**
     * Delete product
     */
    public function destroy($id)
    {
        try {
            $product = Product::findOrFail($id);

            // Verify user owns this product or is admin
            if (auth()->user()->role !== 'admin' && $product->user_id !== auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 403);
            }

            $product->delete();

            return response()->json([
                'success' => true,
                'message' => 'Product deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting product',
            ], 500);
        }
    }

    /**
     * Return live Shopify sync statuses for a set of products (AJAX polling).
     */
    public function statuses(Request $request)
    {
        $ids = collect(explode(',', (string) $request->query('ids')))
            ->map(fn ($id) => (int) trim($id))
            ->filter()
            ->take(200)
            ->all();

        if (empty($ids)) {
            return response()->json(['success' => true, 'products' => []]);
        }

        $query = Product::whereIn('id', $ids);

        // Users only see their own products; admins see all.
        if (auth()->user()->role !== 'admin') {
            $query->where('user_id', auth()->id());
        }

        $products = $query->get([
            'id', 'shopify_status', 'shopify_product_id', 'shopify_error', 'shopify_synced_at',
        ]);

        return response()->json(['success' => true, 'products' => $products]);
    }

    /**
     * List products that failed to sync to Shopify (Shopify API error log).
     */
    public function shopifyErrors()
    {
        $user = auth()->user();

        $query = Product::where('shopify_status', 'failed');

        if ($user->role === 'admin') {
            $query->with('user');
        } else {
            $query->where('user_id', $user->id);
        }

        $failedProducts = $query->latest('updated_at')->paginate(20);

        return view('products.shopify-errors', compact('failedProducts'));
    }

    /**
     * Re-queue a failed product for Shopify sync.
     */
    public function retrySync($id)
    {
        $product = Product::findOrFail($id);

        if (auth()->user()->role !== 'admin' && $product->user_id !== auth()->id()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $product->update(['shopify_status' => 'pending', 'shopify_error' => null]);
        SyncProductToShopify::dispatch($product);

        return response()->json(['success' => true, 'message' => 'Re-queued for Shopify sync']);
    }
}
