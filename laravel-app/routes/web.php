<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProdukController;
use App\Http\Controllers\DesignController;
use App\Http\Controllers\KeranjangController;
use App\Http\Controllers\PesananController;
use App\Http\Controllers\LelangController;
use App\Http\Controllers\PesanController;
use App\Http\Controllers\ProfilController;
use App\Models\Produk;
use App\Models\Design;

// HOME
Route::get('/', function () {
    $products = Produk::with('kategori', 'user')
        ->where('status', 'aktif')
        ->limit(12)
        ->get();
    return view('home', compact('products'));
})->name('home');

// AUTH
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// DESIGN (PASAR KARYA)
Route::get('/pasar-desain', [DesignController::class, 'index'])->name('design.index');
Route::get('/pasar-desain/{id}', [DesignController::class, 'show'])->name('design.show');
Route::get('/design-category/{kategori}', [DesignController::class, 'byCategory'])->name('design.category');
Route::get('/design-search', [DesignController::class, 'search'])->name('design.search');

// PRODUK
Route::get('/product', [ProdukController::class, 'index'])->name('product.index');
Route::get('/product/{id}', [ProdukController::class, 'show'])->name('product.show');
Route::get('/category/{id}', [ProdukController::class, 'byCategory'])->name('product.category');
Route::get('/search', [ProdukController::class, 'search'])->name('product.search');

// LELANG
Route::get('/lelang', [LelangController::class, 'index'])->name('lelang.index');
Route::get('/lelang/{id}', [LelangController::class, 'show'])->name('lelang.show');

// AUTHENTICATED ROUTES
Route::middleware('auth')->group(function () {
    // KERANJANG
    Route::get('/cart', [KeranjangController::class, 'index'])->name('cart.index');
    Route::post('/cart/add', [KeranjangController::class, 'tambah'])->name('cart.add');
    Route::delete('/cart/{id}', [KeranjangController::class, 'hapus'])->name('cart.delete');
    Route::put('/cart/{id}/update', [KeranjangController::class, 'updateJumlah'])->name('cart.update');

    // PESANAN
    Route::post('/checkout', [PesananController::class, 'checkout'])->name('checkout');
    Route::get('/orders', [PesananController::class, 'riwayat'])->name('orders.history');
    Route::get('/orders/{id}', [PesananController::class, 'detail'])->name('orders.detail');

    // LELANG
    Route::post('/lelang/{id}/bid', [LelangController::class, 'submitBid'])->name('lelang.bid');
    Route::get('/lelang-history', [LelangController::class, 'riwayat'])->name('lelang.history');

    // PROFIL
    Route::get('/profile', [ProfilController::class, 'show'])->name('profile.show');
    Route::put('/profile', [ProfilController::class, 'update'])->name('profile.update');

    // PESAN
    Route::get('/messages', [PesanController::class, 'index'])->name('messages.index');
    Route::get('/messages/{id}', [PesanController::class, 'show'])->name('messages.show');
    Route::post('/messages', [PesanController::class, 'store'])->name('messages.store');
});

// MIDTRANS WEBHOOK
Route::post('/midtrans/callback', [PesananController::class, 'webhookMidtrans'])->name('midtrans.callback');
Route::middleware('auth')->group(function () {
    Route::get('/cart', [KeranjangController::class, 'index'])->name('cart.index');
    Route::post('/cart/tambah', [KeranjangController::class, 'tambah'])->name('cart.tambah');
    Route::delete('/cart/{id}', [KeranjangController::class, 'hapus'])->name('cart.hapus');
    Route::put('/cart/{id}/update', [KeranjangController::class, 'updateJumlah'])->name('cart.update');

    // CHECKOUT & PESANAN
    Route::post('/checkout', [PesananController::class, 'checkout'])->name('checkout');
    Route::get('/orders/history', [PesananController::class, 'riwayat'])->name('orders.history');
    Route::get('/orders/{id}', [PesananController::class, 'detail'])->name('orders.detail');

    // LELANG
    Route::get('/lelang', [LelangController::class, 'index'])->name('lelang.index');
    Route::get('/lelang/{id}', [LelangController::class, 'show'])->name('lelang.show');
    Route::post('/lelang/{id}/bid', [LelangController::class, 'submitBid'])->name('lelang.bid');
    Route::get('/lelang/history', [LelangController::class, 'riwayat'])->name('lelang.history');

    // PESAN
    Route::get('/messages', [PesanController::class, 'index'])->name('messages.index');
    Route::get('/messages/{id}', [PesanController::class, 'detail'])->name('messages.detail');
    Route::post('/messages/{id}/send', [PesanController::class, 'send'])->name('messages.send');

    // PROFIL
    Route::get('/profile', [ProfilController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfilController::class, 'edit'])->name('profile.edit');
    Route::put('/profile/update', [ProfilController::class, 'update'])->name('profile.update');
    Route::put('/profile/change-password', [ProfilController::class, 'changePassword'])->name('profile.changePassword');
});

// PROFIL DESAINER (Public)
Route::get('/desainer/{id}', [ProfilController::class, 'designerProfile'])->name('profile.designer');

// WEBHOOK MIDTRANS
Route::post('/webhook/midtrans', [PesananController::class, 'webhookMidtrans'])->name('webhook.midtrans');
