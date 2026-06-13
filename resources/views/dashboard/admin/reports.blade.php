@extends('layouts.dashboard')

@section('title', 'Reports')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-chart-bar"></i> System Reports</h2>
        <p>View comprehensive reports about system usage and user activity.</p>

        <div class="row mt-4">
            <div class="col-md-6 mb-3">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-value">{{ \App\Models\User::count() }}</div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-card">
                    <i class="fas fa-user-shield"></i>
                    <div class="stat-value">{{ \App\Models\User::where('role', 'admin')->count() }}</div>
                    <div class="stat-label">Admin Accounts</div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-card">
                    <i class="fas fa-calendar-check"></i>
                    <div class="stat-value">{{ \Carbon\Carbon::now()->format('Y') }}</div>
                    <div class="stat-label">Current Year</div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="stat-card">
                    <i class="fas fa-clock"></i>
                    <div class="stat-value">{{ \Carbon\Carbon::now()->format('H:i') }}</div>
                    <div class="stat-label">Current Time</div>
                </div>
            </div>
        </div>
    </div>
@endsection
