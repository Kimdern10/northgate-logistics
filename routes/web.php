<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\SitemapController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\WaybillController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('services', [PageController::class, 'services'])->name('services.index');
Route::get('services/{service}', [PageController::class, 'service'])->name('services.show');
Route::get('locations', [PageController::class, 'locations'])->name('locations');
Route::view('about', 'pages.about')->name('about');
Route::view('privacy', 'pages.privacy')->name('privacy');

Route::middleware('throttle:tracking')->group(function () {
    Route::get('track', [TrackingController::class, 'search'])->name('track');
    Route::get('track/{number}', [TrackingController::class, 'show'])->name('track.show');
});

Route::livewire('quote', 'pages::quote')->name('quote');
Route::livewire('ship', 'pages::ship')->name('ship');
Route::livewire('contact', 'pages::contact')->name('contact');

Route::get('ship/booked/{shipment:tracking_number}', [WaybillController::class, 'booked'])->middleware('signed')->name('ship.booked');
Route::get('waybill/{shipment:tracking_number}', [WaybillController::class, 'download'])->middleware('signed')->name('waybill');

Route::get('sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('robots.txt', [SitemapController::class, 'robots'])->name('robots');

Route::middleware(['auth', 'verified'])->group(function () {
    // The starter kit's settings pages link back here.
    Route::redirect('dashboard', 'admin')->name('dashboard');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
