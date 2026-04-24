<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\IceTypeController;
use Illuminate\Support\Facades\Route;


Route::get('/', function () {
    return redirect()->route('login');
});

// Guest routes (login, register)
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('login', [AuthController::class, 'login']);
    Route::get('register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('register', [AuthController::class, 'register']);
});

// Protected routes (require authentication)
Route::middleware('auth')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('dashboard/ice-type-stats', [DashboardController::class, 'iceTypeStats'])->name('dashboard.ice-type-stats');

    // Zone routes
    Route::resource('zones', ZoneController::class)->only(['create', 'store', 'edit', 'update', 'destroy']);
    
    // Customer routes
    Route::resource('customers', CustomerController::class);

    // Driver routes
    Route::resource('drivers', DriverController::class);

    // Stock routes
    Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
    Route::post('stocks', [StockController::class, 'store'])->name('stocks.store');
    Route::get('stocks/realtime/today', [StockController::class, 'realtimeToday'])->name('stocks.realtime.today');

    // Ice Type routes
    Route::resource('ice-types', IceTypeController::class)->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
    
    // Order routes
    Route::resource('orders', OrderController::class)->only(['index', 'update']);
    Route::get('orders/realtime/status', [OrderController::class, 'realtimeStatus'])
        ->middleware('auth:sanctum')
        ->name('orders.realtime.status');
    Route::get('orders/realtime/table', [OrderController::class, 'tableData'])
        ->middleware('auth:sanctum')
        ->name('orders.realtime.table');
    
    // Finance routes
    Route::get('finance', [FinanceController::class, 'index'])->name('finance.index');
    Route::get('finance/reports', [FinanceController::class, 'reports'])->name('finance.reports');
    
    // Expense routes
    Route::resource('expenses', ExpenseController::class);
    
    // Logout
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');
});

// Webhook route (CSRF sudah di-exclude di bootstrap/app.php)
Route::post('webhook/fonnte', [WebhookController::class, 'fonnte']);
