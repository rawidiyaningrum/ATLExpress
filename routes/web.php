<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\InquiryController;
use App\Http\Controllers\RobotsController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

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

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/admin/shipment-tracking/log/{log}/delete', function (\App\Models\ShipmentLog $log) {
        if (! auth()->user()?->isAdminRole()) {
            abort(403);
        }

        $shipment = $log->shipment;

        $log->delete();

        if ($shipment) {
            $latest = $shipment->logs()->latest('timestamp')->first();

            if ($latest && $latest->status) {
                $shipment->update(['status' => $latest->status]);
            } elseif (! $shipment->isActive() && $shipment->logs()->exists()) {
                $latest = $shipment->logs()->latest('timestamp')->first();

                if ($latest && $latest->status) {
                    $shipment->update(['status' => $latest->status]);
                }
            }
        }

        return redirect()->back()->with('success', 'Log tracking berhasil dihapus.');
    })->name('filament.atlexpress-admin.resources.shipment-tracking.log.delete');
});
