<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return redirect()->route('wallet.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth'])->group(function () {
    // Wallet
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/deposit', [WalletController::class, 'showDepositForm'])->name('wallet.deposit.form');
    Route::post('/wallet/deposit', [WalletController::class, 'deposit'])->name('wallet.deposit');
    Route::get('/wallet/transfer', [WalletController::class, 'showTransferForm'])->name('wallet.transfer.form');
    Route::post('/wallet/transfer', [WalletController::class, 'transfer'])->name('wallet.transfer');

    // Follow
    Route::get('/follow', [FollowController::class, 'index'])->name('follow.index');
    Route::post('/follow/{user}', [FollowController::class, 'follow'])->name('follow.follow');
    Route::delete('/follow/{user}', [FollowController::class, 'unfollow'])->name('follow.unfollow');
    Route::get('/followers', [FollowController::class, 'followers'])->name('follow.followers');
    Route::get('/following', [FollowController::class, 'following'])->name('follow.following');

    // PesaPal
    Route::post('/pesapal/deposit', [PaymentController::class, 'initiateDeposit'])->name('pesapal.deposit');
    Route::match(['get', 'post'], '/pesapal/callback', [PaymentController::class, 'callback'])->name('pesapal.callback');
});

// IPN - No CSRF protection
Route::post('/pesapal/ipn', [PaymentController::class, 'ipn'])->name('pesapal.ipn');

require __DIR__.'/auth.php';