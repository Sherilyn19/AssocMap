<?php

use App\Http\Controllers\Admin\MemberController;
use App\Http\Controllers\MemberApplicationPhotoController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/admin/members',
    [MemberController::class, 'index']
)->name('admin.members.index');

// Allow administrators to view the photo linked to an approved member.
Route::get('/admin/members/application/{memberApplication}/photo', MemberApplicationPhotoController::class)
    ->whereUuid('memberApplication')->name('admin.members.application-photo');
