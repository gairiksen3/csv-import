@extends('layouts.dashboard')

@section('title', 'Shopify Errors')

@section('content')
    <div class="content-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="mb-1"><i class="fas fa-triangle-exclamation text-danger"></i> Shopify API Error Log</h2>
                <p class="text-muted mb-0">Products that failed to sync to Shopify via the GraphQL API.</p>
            </div>
            @if(auth()->user()->role === 'admin')
                <a href="{{ route('dashboard.logs') }}" class="btn btn-sm btn-outline-secondary d-none d-md-inline-block">
                    <i class="fas fa-clipboard-list"></i> Full Logs
                </a>
            @endif
        </div>

        @if($failedProducts->total() > 0)
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Title</th>
                            @if(auth()->user()->role === 'admin')<th>User</th>@endif
                            <th>Error</th>
                            <th style="width: 150px;">Last Attempt</th>
                            <th style="width: 110px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($failedProducts as $product)
                            <tr id="error-row-{{ $product->id }}">
                                <td><code>{{ $product->variant_sku ?: '—' }}</code></td>
                                <td>{{ Str::limit($product->title, 35) }}</td>
                                @if(auth()->user()->role === 'admin')
                                    <td><small>{{ $product->user->name ?? '—' }}</small></td>
                                @endif
                                <td>
                                    <div class="text-danger" style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 12px; max-height: 120px; overflow-y: auto;">{{ $product->shopify_error }}</div>
                                </td>
                                <td><small class="text-muted">{{ $product->updated_at?->format('M d, Y H:i') }}</small></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary retry-sync-btn" data-product-id="{{ $product->id }}">
                                        <i class="fas fa-rotate-right"></i> Retry
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4 d-flex flex-column flex-md-row align-items-center justify-content-between gap-2">
                <div class="text-muted small">
                    Showing {{ $failedProducts->firstItem() ?? 0 }}–{{ $failedProducts->lastItem() ?? 0 }} of {{ $failedProducts->total() }} failed syncs
                </div>
                <div>{{ $failedProducts->onEachSide(1)->links() }}</div>
            </div>
        @else
            <div class="alert alert-success" role="alert">
                <i class="fas fa-circle-check"></i> No Shopify sync errors. Everything is in sync.
            </div>
        @endif
    </div>

    @csrf
    <script>
        document.querySelectorAll('.retry-sync-btn').forEach(btn => {
            btn.addEventListener('click', async () => {
                const id = btn.dataset.productId;
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Retrying';
                try {
                    const res = await fetch(`{{ url('products') }}/${id}/retry-sync`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('[name="_token"]').value,
                            'Accept': 'application/json',
                        },
                    });
                    const data = await res.json();
                    if (data.success) {
                        const row = document.getElementById(`error-row-${id}`);
                        if (row) {
                            row.style.transition = 'opacity 0.4s';
                            row.style.opacity = '0.4';
                            btn.innerHTML = '<i class="fas fa-clock"></i> Queued';
                        }
                    } else {
                        alert('Error: ' + (data.message || 'Could not retry'));
                        btn.disabled = false;
                        btn.innerHTML = '<i class="fas fa-rotate-right"></i> Retry';
                    }
                } catch (e) {
                    alert('Error: ' + e.message);
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-rotate-right"></i> Retry';
                }
            });
        });
    </script>
@endsection
