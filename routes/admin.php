<?php

use App\Http\Controllers\WaybillController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::livewire('/', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('shipments', 'pages::admin.shipments.index')->name('shipments.index');
    Route::livewire('shipments/create', 'pages::admin.shipments.form')->name('shipments.create');
    Route::livewire('shipments/{shipment}', 'pages::admin.shipments.show')->name('shipments.show');
    Route::livewire('shipments/{shipment}/edit', 'pages::admin.shipments.form')->name('shipments.edit');
    Route::get('shipments/{shipment}/waybill', [WaybillController::class, 'admin'])->name('shipments.waybill');
    Route::livewire('enquiries', 'pages::admin.enquiries')->name('enquiries');
    Route::livewire('rates', 'pages::admin.rates')->name('rates');
    Route::livewire('services', 'pages::admin.services')->name('services');
    Route::livewire('branches', 'pages::admin.branches')->name('branches');
    Route::livewire('testimonials', 'pages::admin.testimonials')->name('testimonials');
    Route::livewire('faqs', 'pages::admin.faqs')->name('faqs');
    Route::livewire('settings', 'pages::admin.settings')->name('settings');
});
