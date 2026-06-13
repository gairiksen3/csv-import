@extends('layouts.dashboard')

@section('title', 'System Settings')

@section('content')
    <div class="content-card">
        <h2><i class="fas fa-cog"></i> System Settings</h2>
        <p>Configure system-wide settings and preferences.</p>

        <div class="mt-4">
            <form>
                <div class="mb-3">
                    <label for="appName" class="form-label">Application Name</label>
                    <input type="text" class="form-control" id="appName" value="{{ config('app.name') }}" disabled>
                </div>

                <div class="mb-3">
                    <label for="appEnv" class="form-label">Environment</label>
                    <input type="text" class="form-control" id="appEnv" value="{{ config('app.env') }}" disabled>
                </div>

                <div class="mb-3">
                    <label for="appDebug" class="form-label">Debug Mode</label>
                    <input type="text" class="form-control" id="appDebug" value="{{ config('app.debug') ? 'Enabled' : 'Disabled' }}" disabled>
                </div>

                <div class="mb-3">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" checked disabled> Email Notifications
                    </label>
                </div>

                <div class="mb-3">
                    <label class="form-check-label">
                        <input type="checkbox" class="form-check-input" checked disabled> System Backups
                    </label>
                </div>

                <button type="submit" class="btn btn-primary" disabled>Save Settings</button>
            </form>
        </div>
    </div>
@endsection
