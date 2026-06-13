@extends('layouts.dashboard')

@section('title', 'My Products')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-box"></i> My Imported Products</h2>
        <p>View all your imported products.</p>

        @if($products->count() > 0)
            <div class="table-responsive mt-4">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>ID</th>
                            <th>Handle</th>
                            <th>Title</th>
                            <th>Vendor</th>
                            <th>Price</th>
                            <th>Inventory Qty</th>
                            <th>Imported</th>
                            <th>Shopify</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($products as $product)
                            <tr>
                                <td><small>#{{ $product->id }}</small></td>
                                <td>{{ $product->handle ?? '-' }}</td>
                                <td>
                                    <strong>{{ Str::limit($product->title, 30) }}</strong>
                                </td>
                                <td>{{ $product->vendor ?? '-' }}</td>
                                <td>
                                    @if($product->variant_price)
                                        <span class="badge bg-success">${{ number_format($product->variant_price, 2) }}</span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($product->variant_inventory_qty !== null)
                                        <span class="badge bg-info">{{ $product->variant_inventory_qty }}</span>
                                    @else
                                        <span class="badge bg-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $product->created_at ? $product->created_at->format('M d, Y H:i') : '-' }}</small>
                                </td>
                                <td>
                                    @php
                                        $shopifyBadges = ['pending' => 'secondary', 'processing' => 'warning', 'successful' => 'success', 'failed' => 'danger'];
                                        $shopifyStatus = $product->shopify_status ?? 'pending';
                                    @endphp
                                    <span class="badge shopify-status-badge bg-{{ $shopifyBadges[$shopifyStatus] ?? 'secondary' }} text-capitalize"
                                        data-product-id="{{ $product->id }}"
                                        @if($shopifyStatus === 'failed' && $product->shopify_error) title="{{ $product->shopify_error }}" @endif>
                                        {{ $shopifyStatus }}
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary view-product-btn" data-product-id="{{ $product->id }}">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox" style="font-size: 32px; opacity: 0.5;"></i><br>
                                    No products imported yet. <a href="{{ route('dashboard.csv-upload') }}">Start importing</a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="mt-4 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
                <div class="text-muted small">
                    Showing {{ $products->firstItem() ?? 0 }}–{{ $products->lastItem() ?? 0 }} of {{ $products->total() }} products
                </div>
                <div>
                    {{ $products->onEachSide(1)->links() }}
                </div>
            </div>
        @else
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i> You haven't imported any products yet.
                <a href="{{ route('dashboard.csv-upload') }}" class="alert-link">Start by uploading a CSV file</a>.
            </div>
        @endif
    </div>

    <!-- Product Details Modal -->
    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="productModalBody">
                    <div class="spinner-border text-center" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-danger" id="deleteProductBtn">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .product-detail-row {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .product-detail-label {
            font-weight: 600;
            color: #666;
            min-width: 180px;
        }

        .product-detail-value {
            color: #333;
            word-break: break-word;
        }
    </style>

    <script>
        let currentProductId = null;
        let productModal = null;

        function getProductModal() {
            if (!productModal) {
                productModal = new bootstrap.Modal(document.getElementById('productModal'));
            }
            return productModal;
        }

        document.querySelectorAll('.view-product-btn').forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.preventDefault();
                currentProductId = btn.dataset.productId;
                await loadProductDetails(currentProductId);
                getProductModal().show();
            });
        });

        async function loadProductDetails(productId) {
            try {
                const response = await fetch(`{{ route('products.show', ':id') }}`.replace(':id', productId));
                const data = await response.json();

                if (data.success) {
                    renderProductDetails(data.product);
                } else {
                    document.getElementById('productModalBody').innerHTML =
                        '<div class="alert alert-danger">Error loading product details</div>';
                }
            } catch (error) {
                document.getElementById('productModalBody').innerHTML =
                    '<div class="alert alert-danger">Error: ' + error.message + '</div>';
            }
        }

        function shopifyStatusBadge(status) {
            const map = { pending: 'secondary', processing: 'warning', successful: 'success', failed: 'danger' };
            const s = status || 'pending';
            return `<span class="badge bg-${map[s] || 'secondary'} text-capitalize">${s}</span>`;
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        function renderProductDetails(product) {
            const details = `
                <div class="product-detail-row">
                    <div class="product-detail-label">Handle:</div>
                    <div class="product-detail-value">${product.handle || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Title:</div>
                    <div class="product-detail-value"><strong>${product.title || 'N/A'}</strong></div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Description:</div>
                    <div class="product-detail-value">${product.body_html ? product.body_html.substring(0, 200) + '...' : 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Vendor:</div>
                    <div class="product-detail-value">${product.vendor || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Product Type:</div>
                    <div class="product-detail-value">${product.product_type || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Tags:</div>
                    <div class="product-detail-value">${product.tags || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Published:</div>
                    <div class="product-detail-value">
                        ${product.published ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">SKU:</div>
                    <div class="product-detail-value">${product.variant_sku || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Price:</div>
                    <div class="product-detail-value">
                        ${product.variant_price ? '$' + parseFloat(product.variant_price).toFixed(2) : 'N/A'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Compare At Price:</div>
                    <div class="product-detail-value">
                        ${product.variant_compare_at_price ? '$' + parseFloat(product.variant_compare_at_price).toFixed(2) : 'N/A'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Inventory Qty:</div>
                    <div class="product-detail-value">${product.variant_inventory_qty !== null ? product.variant_inventory_qty : 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Requires Shipping:</div>
                    <div class="product-detail-value">
                        ${product.variant_requires_shipping ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Taxable:</div>
                    <div class="product-detail-value">
                        ${product.variant_taxable ? '<span class="badge bg-success">Yes</span>' : '<span class="badge bg-danger">No</span>'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Inventory Tracker:</div>
                    <div class="product-detail-value">${product.variant_inventory_tracker || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Inventory Policy:</div>
                    <div class="product-detail-value">${product.variant_inventory_policy || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Fulfillment Service:</div>
                    <div class="product-detail-value">${product.variant_fulfillment_service || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Weight:</div>
                    <div class="product-detail-value">
                        ${product.variant_weight ? product.variant_weight + ' ' + (product.variant_weight_unit || 'kg') : 'N/A'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Image:</div>
                    <div class="product-detail-value">
                        ${product.image_src ? '<img src="' + product.image_src + '" alt="' + (product.image_alt_text || 'Product') + '" style="max-width: 200px; max-height: 150px;">' : 'N/A'}
                    </div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Image Alt Text:</div>
                    <div class="product-detail-value">${product.image_alt_text || 'N/A'}</div>
                </div>
                <div class="product-detail-row">
                    <div class="product-detail-label">Shopify Status:</div>
                    <div class="product-detail-value">${shopifyStatusBadge(product.shopify_status)}</div>
                </div>
                ${product.shopify_product_id ? `
                <div class="product-detail-row">
                    <div class="product-detail-label">Shopify Product ID:</div>
                    <div class="product-detail-value">${product.shopify_product_id}</div>
                </div>` : ''}
                ${product.shopify_synced_at ? `
                <div class="product-detail-row">
                    <div class="product-detail-label">Last Synced:</div>
                    <div class="product-detail-value">${new Date(product.shopify_synced_at).toLocaleString()}</div>
                </div>` : ''}
                ${product.shopify_error ? `
                <div class="product-detail-row">
                    <div class="product-detail-label">Shopify Error:</div>
                    <div class="product-detail-value">
                        <div class="alert alert-danger mb-0" style="white-space: pre-wrap; word-break: break-word;">${escapeHtml(product.shopify_error)}</div>
                    </div>
                </div>` : ''}
            `;

            document.getElementById('productModalBody').innerHTML = details;
        }

        document.getElementById('deleteProductBtn').addEventListener('click', async () => {
            if (confirm('Are you sure you want to delete this product? This action cannot be undone.')) {
                try {
                    const response = await fetch(`{{ route('products.destroy', ':id') }}`.replace(':id', currentProductId), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                        }
                    });

                    const data = await response.json();

                    if (data.success) {
                        getProductModal().hide();
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                } catch (error) {
                    alert('Error deleting product: ' + error.message);
                }
            }
        });

        // ---- Real-time Shopify status polling ----
        (function () {
            const STATUS_COLORS = { pending: 'secondary', processing: 'warning', successful: 'success', failed: 'danger' };
            const ACTIVE = ['pending', 'processing'];
            const POLL_MS = 3000;
            const MAX_POLLS = 100; // ~5 minutes safety cap
            let polls = 0;

            function badges() {
                return Array.from(document.querySelectorAll('.shopify-status-badge'));
            }

            function hasActive() {
                return badges().some(b => ACTIVE.includes(b.textContent.trim().toLowerCase()));
            }

            function applyStatus(el, status, error) {
                status = status || 'pending';
                el.className = `badge shopify-status-badge bg-${STATUS_COLORS[status] || 'secondary'} text-capitalize`;
                el.textContent = status;
                if (status === 'failed' && error) {
                    el.title = error;
                } else {
                    el.removeAttribute('title');
                }
            }

            async function poll() {
                polls++;
                const ids = badges().map(b => b.dataset.productId);
                if (!ids.length) return;

                try {
                    const res = await fetch(`{{ route('products.statuses') }}?ids=${ids.join(',')}`, {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await res.json();
                    if (data.success) {
                        data.products.forEach(p => {
                            const el = document.querySelector(`.shopify-status-badge[data-product-id="${p.id}"]`);
                            if (el) applyStatus(el, p.shopify_status, p.shopify_error);
                        });
                    }
                } catch (e) {
                    // network hiccup — keep trying until the cap
                }

                if (hasActive() && polls < MAX_POLLS) {
                    setTimeout(poll, POLL_MS);
                }
            }

            // Only start polling if something is still syncing.
            document.addEventListener('DOMContentLoaded', () => {
                if (hasActive()) setTimeout(poll, POLL_MS);
            });
        })();
    </script>
@endsection
