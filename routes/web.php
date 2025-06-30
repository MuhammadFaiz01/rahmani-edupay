<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\OrtuController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\Admin\TagihanController as AdminTagihanController;
use App\Http\Controllers\Admin\PembayaranController;
use App\Http\Controllers\Admin\PengumumanController as AdminPengumumanController;
use App\Http\Controllers\Ortu\DashboardController as OrtuDashboardController;
use App\Http\Controllers\Ortu\TagihanController as OrtuTagihanController;
use App\Http\Controllers\Ortu\PengumumanController as OrtuPengumumanController;
use App\Http\Controllers\PaymentController;

// Public routes
Route::get('/', function () {
    return redirect()->route('login');
});

// Authentication routes (will be added when Breeze is installed)
require __DIR__.'/auth.php';

// Payment webhook (public)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])->name('payment.webhook');
Route::get('/payment/finish', [PaymentController::class, 'finish'])->name('payment.finish');
Route::get('/payment/unfinish', [PaymentController::class, 'unfinish'])->name('payment.unfinish');
Route::get('/payment/error', [PaymentController::class, 'error'])->name('payment.error');

// Protected routes
Route::middleware(['auth'])->group(function () {
    
    // Admin routes
    Route::middleware(['admin'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        
        // Orang Tua management
        Route::resource('ortu', OrtuController::class);
        
        // Siswa management
        Route::resource('siswa', SiswaController::class);
        
        // Tagihan management
        Route::resource('tagihan', AdminTagihanController::class);
        Route::post('/tagihan/update-overdue', [AdminTagihanController::class, 'updateOverdue'])->name('tagihan.update-overdue');
        
        // Pembayaran management
        Route::get('/pembayaran', [PembayaranController::class, 'index'])->name('pembayaran.index');
        Route::get('/pembayaran/{pembayaran}', [PembayaranController::class, 'show'])->name('pembayaran.show');
        Route::post('/pembayaran/{pembayaran}/verify', [PembayaranController::class, 'verify'])->name('pembayaran.verify');
        Route::post('/pembayaran/{pembayaran}/upload-proof', [PembayaranController::class, 'uploadProof'])->name('pembayaran.upload-proof');
        Route::post('/pembayaran/report', [PembayaranController::class, 'report'])->name('pembayaran.report');
        Route::get('/pembayaran/report/daily', [PembayaranController::class, 'dailyReport'])->name('pembayaran.daily-report');
        Route::get('/pembayaran/report/monthly', [PembayaranController::class, 'monthlyReport'])->name('pembayaran.monthly-report');
        
        // Pengumuman management
        Route::resource('pengumuman', AdminPengumumanController::class);
    });
    
    // Orang Tua routes
    Route::middleware(['ortu'])->prefix('ortu')->name('ortu.')->group(function () {
        Route::get('/dashboard', [OrtuDashboardController::class, 'index'])->name('dashboard');
        
        // Tagihan and Payment
        Route::get('/tagihan', [OrtuTagihanController::class, 'index'])->name('tagihan.index');
        Route::get('/tagihan/{tagihan}', [OrtuTagihanController::class, 'show'])->name('tagihan.show');
        Route::post('/tagihan/{tagihan}/create-payment', [OrtuTagihanController::class, 'createPayment'])->name('tagihan.create-payment');
        Route::post('/tagihan/{tagihan}/upload-proof', [OrtuTagihanController::class, 'uploadProof'])->name('tagihan.upload-proof');
        Route::get('/pembayaran/{pembayaran}/receipt', [OrtuTagihanController::class, 'printReceipt'])->name('pembayaran.receipt');
        Route::get('/pembayaran/history', [OrtuTagihanController::class, 'paymentHistory'])->name('pembayaran.history');
        
        // Pengumuman
        Route::get('/pengumuman', [OrtuPengumumanController::class, 'index'])->name('pengumuman.index');
        Route::get('/pengumuman/{pengumuman}', [OrtuPengumumanController::class, 'show'])->name('pengumuman.show');
    });
    
    // Redirect based on role
    Route::get('/dashboard', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif (auth()->user()->isOrtu()) {
            return redirect()->route('ortu.dashboard');
        }
        return redirect()->route('login');
    })->name('dashboard');
});
