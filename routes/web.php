<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CsvImportController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\NotificationController;

Route::get('/', [HomeController::class, 'index']);

// Registration Routes
Route::get('/register', [RegistrationController::class, 'show'])->name('register');
Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');

// Login Routes
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->name('login.authenticate');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Dashboard Routes (Protected by auth middleware)
Route::middleware('auth')->group(function () {
    // Main Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Shared Routes (Admin and Users)
    Route::get('/dashboard/help', function () {
        return view('dashboard.help');
    })->name('dashboard.help');

    // Admin Routes
    Route::middleware('admin')->group(function () {
        Route::get('/dashboard/users', function () {
            return view('dashboard.admin.users');
        })->name('dashboard.users');

        Route::get('/dashboard/reports', function () {
            return view('dashboard.admin.reports');
        })->name('dashboard.reports');

        Route::get('/dashboard/settings', function () {
            return view('dashboard.admin.settings');
        })->name('dashboard.settings');

        // Admin Products Routes
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');

        // Log Viewer (import + Shopify sync events)
        Route::get('/dashboard/logs', [LogViewerController::class, 'index'])->name('dashboard.logs');
        Route::delete('/dashboard/logs', [LogViewerController::class, 'clear'])->name('dashboard.logs.clear');
    });

    // User Routes
    Route::middleware('user')->group(function () {
        Route::get('/dashboard/profile', function () {
            return view('dashboard.profile');
        })->name('dashboard.profile');

        Route::get('/dashboard/files', function () {
            return view('dashboard.files');
        })->name('dashboard.files');

        // CSV Import Routes
        Route::get('/dashboard/csv-upload', function () {
            return view('dashboard.csv-upload');
        })->name('dashboard.csv-upload');

        Route::post('/csv-import', [CsvImportController::class, 'store'])->name('csv-import.store');
        Route::get('/csv-import/status/{id}', [CsvImportController::class, 'checkStatus'])->name('csv-import.check-status');
        Route::get('/csv-import/history', [CsvImportController::class, 'history'])->name('csv-import.history');

        // User Products Routes
        Route::get('/my-products', [ProductController::class, 'index'])->name('products.user-list');
    });

    // Product AJAX Routes (Both Admin and User)
    // NOTE: keep specific paths before the /products/{id} wildcard.
    Route::get('/product-statuses', [ProductController::class, 'statuses'])->name('products.statuses');
    Route::get('/shopify-errors', [ProductController::class, 'shopifyErrors'])->name('products.shopify-errors');
    Route::post('/products/{id}/retry-sync', [ProductController::class, 'retrySync'])->name('products.retry-sync');
    Route::get('/products/{id}', [ProductController::class, 'show'])->name('products.show');
    Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');

    // Notifications (Both Admin and User)
    Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');
});
