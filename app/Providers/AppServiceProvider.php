<?php

namespace App\Providers;

use App\Models\Order;
use App\Services\FcmService;
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

            $latestUpdatedOrder = Auth::check()
                ? Order::query()->latest('updated_at')->latest('id')->first()
                : null;
            $latestUpdateToken = $latestUpdatedOrder
                ? ($latestUpdatedOrder->id . '-' . $latestUpdatedOrder->updated_at->format('YmdHisu'))
                : '';

            $view->with('pendingOrdersCount', $pendingOrdersCount);
            $view->with('latestOrderIdGlobal', $latestOrderId);
            $view->with('latestUpdateTokenGlobal', $latestUpdateToken);
        });

        // Pre-warm FCM access token di background setelah boot selesai.
        // Ini mencegah delay pada order pertama karena harus generate JWT
        // + HTTP call ke Google OAuth2 secara synchronous saat webhook masuk.
        $this->callAfterResolving(FcmService::class, function (FcmService $fcm) {
            // Wrap dalam try-catch agar boot tidak gagal jika credentials belum dikonfigurasi
            rescue(fn () => $fcm->warmAccessToken(), null, false);
        });
    }
}
