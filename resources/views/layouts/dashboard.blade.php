<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title') - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
        }

        .dashboard-container {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Styling */
        .sidebar {
            width: 250px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 0;
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            overflow-y: auto;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
            z-index: 1000;
        }

        .sidebar-header {
            padding: 25px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            text-align: center;
        }

        .sidebar-header h2 {
            font-size: 20px;
            margin: 0;
            font-weight: 700;
        }

        .sidebar-header .role-badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            margin-top: 10px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .sidebar-menu {
            list-style: none;
            padding: 20px 0;
        }

        .sidebar-menu li {
            margin: 0;
        }

        .sidebar-menu a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #fff;
            color: #fff;
        }

        .sidebar-menu i {
            width: 25px;
            margin-right: 15px;
            text-align: center;
            font-size: 18px;
        }

        .sidebar-menu span {
            font-size: 14px;
            font-weight: 500;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 0;
        }

        .sidebar-footer a {
            display: flex;
            align-items: center;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .sidebar-footer a:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left-color: #fff;
        }

        .sidebar-footer i {
            width: 25px;
            margin-right: 15px;
            text-align: center;
            font-size: 18px;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 30px;
        }

        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-left: -30px;
            margin-right: -30px;
            margin-top: -30px;
            padding-left: 30px;
        }

        .top-bar h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin: 0;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .user-info .user-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 18px;
        }

        .user-name {
            font-size: 14px;
            color: #333;
            font-weight: 500;
        }

        /* Content Cards */
        .content-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .content-card h2 {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }

        .content-card p {
            color: #666;
            line-height: 1.6;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            text-align: center;
        }

        .stat-card i {
            font-size: 32px;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-card .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #333;
        }

        .stat-card .stat-label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            margin-top: 5px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                width: 200px;
            }

            .main-content {
                margin-left: 200px;
                padding: 20px;
            }

            .top-bar {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .top-bar h1 {
                font-size: 22px;
            }

            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        @media (max-width: 576px) {
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }

            .main-content {
                margin-left: 0;
                padding: 15px;
            }

            .sidebar-header {
                padding: 15px;
            }

            .sidebar-menu a {
                padding: 12px 20px;
            }

            .sidebar-footer {
                position: relative;
            }

            .top-bar {
                margin-left: -15px;
                margin-right: -15px;
                margin-top: -15px;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @yield('styles')
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Dashboard</h2>
                <div class="role-badge">{{ auth()->user()->role }}</div>
            </div>

            <ul class="sidebar-menu">
                <li>
                    <a href="{{ route('dashboard') }}" class="@if(Route::currentRouteName() === 'dashboard') active @endif">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                @if(auth()->user()->role === 'admin')
                    <li>
                        <a href="{{ route('dashboard.users') }}" class="@if(Route::currentRouteName() === 'dashboard.users') active @endif">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.reports') }}" class="@if(Route::currentRouteName() === 'dashboard.reports') active @endif">
                            <i class="fas fa-chart-bar"></i>
                            <span>Reports</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.settings') }}" class="@if(Route::currentRouteName() === 'dashboard.settings') active @endif">
                            <i class="fas fa-cog"></i>
                            <span>Settings</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.index') }}" class="@if(Route::currentRouteName() === 'products.index') active @endif">
                            <i class="fas fa-box"></i>
                            <span>Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.shopify-errors') }}" class="@if(Route::currentRouteName() === 'products.shopify-errors') active @endif">
                            <i class="fas fa-triangle-exclamation"></i>
                            <span>Shopify Errors</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.logs') }}" class="@if(Route::currentRouteName() === 'dashboard.logs') active @endif">
                            <i class="fas fa-clipboard-list"></i>
                            <span>Logs</span>
                        </a>
                    </li>
                @else
                    <li>
                        <a href="{{ route('dashboard.profile') }}" class="@if(Route::currentRouteName() === 'dashboard.profile') active @endif">
                            <i class="fas fa-user"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('dashboard.csv-upload') }}" class="@if(Route::currentRouteName() === 'dashboard.csv-upload') active @endif">
                            <i class="fas fa-file-csv"></i>
                            <span>Import CSV</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.user-list') }}" class="@if(Route::currentRouteName() === 'products.user-list') active @endif">
                            <i class="fas fa-box"></i>
                            <span>My Products</span>
                        </a>
                    </li>
                    <li>
                        <a href="{{ route('products.shopify-errors') }}" class="@if(Route::currentRouteName() === 'products.shopify-errors') active @endif">
                            <i class="fas fa-triangle-exclamation"></i>
                            <span>Shopify Errors</span>
                        </a>
                    </li>
                @endif
            </ul>

            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST" style="margin: 0;">
                    @csrf
                    <button type="submit" style="width: 100%; background: none; border: none; cursor: pointer; padding: 15px 25px; color: rgba(255, 255, 255, 0.9); text-decoration: none; display: flex; align-items: center; transition: all 0.3s ease;">
                        <i class="fas fa-sign-out-alt" style="width: 25px; margin-right: 15px; text-align: center; font-size: 18px;"></i>
                        <span style="font-size: 14px; font-weight: 500;">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Bar -->
            <div class="top-bar">
                <h1>@yield('title')</h1>
                <div class="user-info" style="display: flex; align-items: center; gap: 20px;">
                    @php
                        $unreadNotifications = auth()->user()->unreadNotifications;
                        $recentNotifications = auth()->user()->notifications()->latest()->take(10)->get();
                        $notifLevelColor = ['error' => '#dc3545', 'warning' => '#ffc107', 'info' => '#0dcaf0'];
                    @endphp
                    <!-- Notification Bell -->
                    <div class="dropdown">
                        <button class="btn position-relative" type="button" data-bs-toggle="dropdown" aria-expanded="false"
                                style="background: none; border: none; font-size: 20px; color: #667eea;">
                            <i class="fas fa-bell"></i>
                            @if($unreadNotifications->count() > 0)
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                                    {{ $unreadNotifications->count() > 9 ? '9+' : $unreadNotifications->count() }}
                                </span>
                            @endif
                        </button>
                        <div class="dropdown-menu dropdown-menu-end shadow" style="width: 340px; max-height: 420px; overflow-y: auto;">
                            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                                <strong>Notifications</strong>
                                @if($unreadNotifications->count() > 0)
                                    <form action="{{ route('notifications.read-all') }}" method="POST" style="margin:0;">
                                        @csrf
                                        <button type="submit" class="btn btn-link btn-sm p-0" style="text-decoration: none;">Mark all read</button>
                                    </form>
                                @endif
                            </div>
                            @forelse($recentNotifications as $note)
                                <a href="{{ route('notifications.open', $note->id) }}"
                                   class="dropdown-item d-flex align-items-start gap-2 py-2 {{ $note->read_at ? '' : 'bg-light' }}"
                                   style="white-space: normal;">
                                    <i class="fas fa-circle mt-1" style="font-size: 8px; color: {{ $notifLevelColor[$note->data['level'] ?? 'info'] ?? '#6c757d' }};"></i>
                                    <span>
                                        <span class="d-block fw-semibold" style="font-size: 13px;">{{ $note->data['title'] ?? 'Notification' }}</span>
                                        <small class="text-muted">{{ $note->data['message'] ?? '' }}</small>
                                        <small class="d-block text-muted" style="font-size: 11px;">{{ $note->created_at->diffForHumans() }}</small>
                                    </span>
                                </a>
                            @empty
                                <div class="dropdown-item text-muted text-center py-3">No notifications</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="user-avatar">
                        {{ substr(auth()->user()->name, 0, 1) }}
                    </div>
                    <div>
                        <div class="user-name">{{ auth()->user()->name }}</div>
                        <small style="color: #999;">{{ auth()->user()->email }}</small>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Errors:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @yield('scripts')
</body>
</html>
