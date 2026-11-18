<?php

use App\Http\Controllers\PublicGisController;
use App\Http\Controllers\GisPhotoController;
use App\Http\Middleware\GisRequestGuard;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicMemberApplicationController;

// Public application routes use their own submission limit and location validation.
Route::get('/how-to-apply', [PublicMemberApplicationController::class, 'guide'])->name('public.application.guide');
Route::get('/membership/apply/{location}', [PublicMemberApplicationController::class, 'create'])->whereUuid('location')->name('public.application.create');
Route::post('/membership/apply/{location}', [PublicMemberApplicationController::class, 'store'])->whereUuid('location')->middleware('throttle:public-application')->name('public.application.store');
Route::get('/application-received', [PublicMemberApplicationController::class, 'received'])->name('public.application.received');

Route::middleware([GisRequestGuard::class, 'throttle:gis-read'])->group(function () {
    Route::get('/map', [PublicGisController::class, 'index'])->name('public.gis');
    Route::get('/map/data', [PublicGisController::class, 'data'])->name('public.gis.data');
    Route::get('/map/site-types', [PublicGisController::class, 'siteTypes'])->name('public.gis.site-types');
    Route::get('/map/associations', [PublicGisController::class, 'associations'])->name('public.gis.associations');
    Route::get('/map/{location}/photos', [GisPhotoController::class, 'index'])->whereUuid('location')->name('public.gis.photos.index');
    Route::get('/map/{location}/photos/{photo}/{variant}', [GisPhotoController::class, 'image'])
        ->whereUuid('location')->whereUuid('photo')->whereIn('variant', ['thumbnail', 'display'])->name('public.gis.photos.image');
});
