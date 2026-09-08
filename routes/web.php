<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\InquiryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/tentang-kami', [PageController::class, 'about'])->name('about');
Route::get('/layanan', [PageController::class, 'services'])->name('services');
Route::get('/layanan/{slug}', [PageController::class, 'serviceDetail'])->name('service.detail');
Route::get('/cek-resi', [PageController::class, 'tracking'])->name('tracking');
Route::get('/cek-tarif', [PageController::class, 'tariff'])->name('tariff');
Route::get('/berita', [PostController::class, 'index'])->name('posts.index');
Route::get('/berita/{slug}', [PostController::class, 'show'])->name('posts.show');
Route::get('/kontak', [PageController::class, 'contact'])->name('contact');
Route::post('/kontak', [InquiryController::class, 'store'])->name('inquiry.store');
