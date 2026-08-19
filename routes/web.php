<?php

use App\Http\Controllers\AdminImportController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [VerificationController::class, 'index'])->name('verification.index');
Route::get('/verify', [VerificationController::class, 'index'])->name('verification.show');
Route::post('/verify', [VerificationController::class, 'verify'])->name('verification.verify');
Route::get('/complaints/new', [ComplaintController::class, 'create'])->name('complaints.create');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', [AdminImportController::class, 'index'])->name('imports.index');
        Route::post('/imports', [AdminImportController::class, 'store'])->name('imports.store');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
        Route::put('/users/password', [AdminUserController::class, 'updatePassword'])->name('users.password.update');
    });
