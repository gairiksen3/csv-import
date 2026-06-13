<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The UI is built with Bootstrap 5, so render pagination with the
        // matching Bootstrap markup instead of the default Tailwind styles.
        Paginator::useBootstrapFive();
    }
}
