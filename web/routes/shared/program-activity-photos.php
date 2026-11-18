<?php

use App\Http\Controllers\ProgramActivityPhotoController;
use Illuminate\Support\Facades\Route;

// Every route remains inside its caller's existing role middleware.
Route::get('/'. $activityPhotoPrefix .'/program-activities/{activity}/photos', [ProgramActivityPhotoController::class, 'index'])->middleware('throttle:gis-read')->whereUuid('activity')->name($activityPhotoPrefix.'.program-activities.photos.index');
Route::get('/'. $activityPhotoPrefix .'/program-activities/{activity}/photos/{photo}/{variant}', [ProgramActivityPhotoController::class, 'image'])->middleware('throttle:gis-read')->whereUuid('activity')->whereUuid('photo')->whereIn('variant', ['thumbnail', 'display'])->name($activityPhotoPrefix.'.program-activities.photos.image');
if ($activityPhotoPrefix !== 'association') {
    Route::post('/'. $activityPhotoPrefix .'/program-activities/{activity}/photos', [ProgramActivityPhotoController::class, 'store'])->middleware('throttle:gis-write')->whereUuid('activity')->name($activityPhotoPrefix.'.program-activities.photos.store');
    Route::patch('/'. $activityPhotoPrefix .'/program-activities/{activity}/photos/order', [ProgramActivityPhotoController::class, 'reorder'])->middleware('throttle:gis-write')->whereUuid('activity')->name($activityPhotoPrefix.'.program-activities.photos.reorder');
    Route::delete('/'. $activityPhotoPrefix .'/program-activities/{activity}/photos/{photo}', [ProgramActivityPhotoController::class, 'destroy'])->middleware('throttle:gis-write')->whereUuid('activity')->whereUuid('photo')->name($activityPhotoPrefix.'.program-activities.photos.destroy');
    Route::post('/'. $activityPhotoPrefix .'/program-activities/{activity}/photos/{photo}/reconcile', [ProgramActivityPhotoController::class, 'reconcile'])->middleware('throttle:gis-write')->whereUuid('activity')->whereUuid('photo')->name($activityPhotoPrefix.'.program-activities.photos.reconcile');
}
