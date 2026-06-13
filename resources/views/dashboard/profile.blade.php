@extends('layouts.dashboard')

@section('title', 'Profile')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-user-circle"></i> My Profile</h2>
        <p>View and edit your profile information.</p>

        <form method="POST" class="mt-4">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="name" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ auth()->user()->name }}" disabled>
            </div>

            <div class="mb-3">
                <label for="email" class="form-label">Email Address</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ auth()->user()->email }}" disabled>
            </div>

            <div class="mb-3">
                <label for="role" class="form-label">Account Type</label>
                <input type="text" class="form-control" id="role" value="{{ ucfirst(auth()->user()->role) }}" disabled>
            </div>

            <div class="mb-3">
                <label for="joined" class="form-label">Member Since</label>
                <input type="text" class="form-control" id="joined" value="{{ auth()->user()->created_at->format('F d, Y') }}" disabled>
            </div>

            <button type="submit" class="btn btn-primary" disabled>Update Profile</button>
        </form>
    </div>
@endsection
