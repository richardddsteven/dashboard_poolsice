<?php

namespace App\Providers;

use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
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
        // Share pending order badge count for sidebar on every authenticated page.
        View::composer('layouts.dashboard', function ($view) {
            $pendingOrdersCount = Auth::check()
                ? Order::where('status', 'pending')->count()
                : 0;

            $latestOrderId = Auth::check()
                ? (Order::max('id') ?? 0)
                : 0;

            $view->with('pendingOrdersCount', $pendingOrdersCount);
            $view->with('latestOrderIdGlobal', $latestOrderId);
        });
    }
}
