@extends('layouts.dashboard')

@section('title', 'Logs')

@section('content')
    <div class="content-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <div>
                <h2 class="mb-1"><i class="fas fa-clipboard-list"></i> Import &amp; Shopify Logs</h2>
                <p class="text-muted mb-0">Events from CSV imports and Shopify GraphQL syncs.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <!-- Level filter -->
                <div class="btn-group" role="group">
                    @php $levels = ['' => 'All', 'info' => 'Info', 'warning' => 'Warning', 'error' => 'Error']; @endphp
                    @foreach($levels as $value => $label)
                        <a href="{{ route('dashboard.logs', $value ? ['level' => $value] : []) }}"
                           class="btn btn-sm {{ $filterLevel === $value ? 'btn-primary' : 'btn-outline-primary' }}">
                            {{ $label }}
                        </a>
                    @endforeach
                </div>

                <a href="{{ route('dashboard.logs', $filterLevel ? ['level' => $filterLevel] : []) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-sync"></i> Refresh
                </a>

                <form action="{{ route('dashboard.logs.clear') }}" method="POST"
                      onsubmit="return confirm('Clear the entire import log file?');" style="margin:0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="fas fa-trash"></i> Clear Log
                    </button>
                </form>
            </div>
        </div>

        @if(!$logExists)
            <div class="alert alert-info" role="alert">
                <i class="fas fa-info-circle"></i> No log file yet. Run a CSV import to generate log entries.
            </div>
        @elseif(count($entries) === 0)
            <div class="alert alert-secondary" role="alert">
                <i class="fas fa-inbox"></i> No log entries match this filter.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 170px;">Time</th>
                            <th style="width: 100px;">Level</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                            $levelBadge = ['ERROR' => 'danger', 'WARNING' => 'warning', 'INFO' => 'info'];
                        @endphp
                        @foreach($entries as $entry)
                            <tr>
                                <td><small class="text-muted">{{ $entry['datetime'] }}</small></td>
                                <td>
                                    <span class="badge bg-{{ $levelBadge[$entry['level']] ?? 'secondary' }}">{{ $entry['level'] }}</span>
                                </td>
                                <td style="white-space: pre-wrap; word-break: break-word; font-family: monospace; font-size: 12px;">{{ $entry['message'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted small mb-0">Showing the most recent {{ count($entries) }} entr{{ count($entries) === 1 ? 'y' : 'ies' }} (newest first).</p>
        @endif
    </div>
@endsection
