<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\ZoneController;
use App\Http\Controllers\FinanceController;
use App\Http\Controllers\ExpenseController;
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
    Route::resource('zones', ZoneController::class)->only(['create', 'store']);
    
    // Customer routes
    Route::resource('customers', CustomerController::class);
    
    // Order routes
    Route::resource('orders', OrderController::class)->only(['index', 'update']);
    
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
