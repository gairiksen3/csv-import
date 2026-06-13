@extends('layouts.dashboard')

@section('title', 'Admin Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-users"></i>
            <div class="stat-value">{{ \App\Models\User::count() }}</div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-shield"></i>
            <div class="stat-value">{{ \App\Models\User::where('role', 'admin')->count() }}</div>
            <div class="stat-label">Admin Users</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-user-check"></i>
            <div class="stat-value">{{ \App\Models\User::where('role', 'user')->count() }}</div>
            <div class="stat-label">Regular Users</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-today"></i>
            <div class="stat-value">{{ \Carbon\Carbon::now()->format('d-m-Y') }}</div>
            <div class="stat-label">Today's Date</div>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="content-card">
        <h2><i class="fas fa-crown"></i> Welcome, Admin!</h2>
        <p>
            You have full access to all administrative functions. Manage users, view reports, and configure system settings from the menu on the left.
        </p>
    </div>

    <!-- Quick Links -->
    <div class="content-card">
        <h2><i class="fas fa-link"></i> Quick Links</h2>
        <div class="row">
            <div class="col-md-3 mb-3">
                <a href="{{ route('dashboard.users') }}" class="btn btn-primary w-100">
                    <i class="fas fa-users"></i> Manage Users
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="{{ route('products.index') }}" class="btn btn-success w-100">
                    <i class="fas fa-box"></i> View Products
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="{{ route('dashboard.reports') }}" class="btn btn-info w-100">
                    <i class="fas fa-chart-bar"></i> View Reports
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="{{ route('dashboard.settings') }}" class="btn btn-warning w-100">
                    <i class="fas fa-cog"></i> System Settings
                </a>
            </div>
        </div>
    </div>
@endsection
