@extends('layouts.dashboard')

@section('title', 'User Dashboard')

@section('content')
    <!-- Stats Grid -->
    <div class="stats-grid">
        <div class="stat-card">
            <i class="fas fa-user"></i>
            <div class="stat-value">{{ auth()->user()->name }}</div>
            <div class="stat-label">Your Name</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-envelope"></i>
            <div class="stat-value" style="font-size: 14px; word-break: break-all;">{{ auth()->user()->email }}</div>
            <div class="stat-label">Your Email</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-calendar-alt"></i>
            <div class="stat-value">{{ auth()->user()->created_at->format('d-m-Y') }}</div>
            <div class="stat-label">Member Since</div>
        </div>
        <div class="stat-card">
            <i class="fas fa-shield-alt"></i>
            <div class="stat-value">{{ ucfirst(auth()->user()->role) }}</div>
            <div class="stat-label">Account Type</div>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="content-card">
        <h2><i class="fas fa-smile"></i> Welcome, {{ auth()->user()->name }}!</h2>
        <p>
            You are successfully logged in to your account. You can manage your profile, upload and manage files, and view your account information from the menu on the left.
        </p>
    </div>

    <!-- Quick Actions -->
    <div class="content-card">
        <h2><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="row">
            <div class="col-md-3 mb-3">
                <a href="{{ route('dashboard.profile') }}" class="btn btn-primary w-100">
                    <i class="fas fa-user-edit"></i> Edit Profile
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="{{ route('dashboard.csv-upload') }}" class="btn btn-warning w-100">
                    <i class="fas fa-file-csv"></i> Import CSV
                </a>
            </div>
            <div class="col-md-3 mb-3">
                <a href="{{ route('products.user-list') }}" class="btn btn-success w-100">
                    <i class="fas fa-box"></i> My Products
                </a>
            </div>
        </div>
    </div>

    <!-- Account Info -->
    <div class="content-card">
        <h2><i class="fas fa-info-circle"></i> Account Information</h2>
        <div class="table-responsive">
            <table class="table">
                <tr>
                    <td style="font-weight: 600; color: #666;">Full Name:</td>
                    <td>{{ auth()->user()->name }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; color: #666;">Email Address:</td>
                    <td>{{ auth()->user()->email }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; color: #666;">Member Since:</td>
                    <td>{{ auth()->user()->created_at->format('F d, Y') }}</td>
                </tr>
                <tr>
                    <td style="font-weight: 600; color: #666;">Account Status:</td>
                    <td><span class="badge bg-success">Active</span></td>
                </tr>
            </table>
        </div>
    </div>
@endsection
