<?php

use App\Http\Controllers\FieldOfficer\MemberApplicationController as FieldOfficerMemberApplicationController;
use App\Http\Controllers\MemberApplicationPhotoController;
use App\Http\Middleware\MemberApplicationEvidenceHeaders;
use Illuminate\Support\Facades\Route;

Route::patch('/field-officer/member-applications/{memberApplication}/return', [FieldOfficerMemberApplicationController::class, 'returnToAssociation'])
    ->whereUuid('memberApplication')->middleware(MemberApplicationEvidenceHeaders::class)->name('field-officer.member-applications.return');

Route::get(
    '/field-officer/member-applications',
    [
        FieldOfficerMemberApplicationController::class,
        'index',
    ]
)->middleware(MemberApplicationEvidenceHeaders::class)->name(
    'field-officer.member-applications.index'
);

Route::get('/field-officer/member-applications/{memberApplication}/photo', MemberApplicationPhotoController::class)
    ->whereUuid('memberApplication')
    ->middleware(MemberApplicationEvidenceHeaders::class)
    ->name('field-officer.member-applications.photo');

Route::patch(
    '/field-officer/member-applications/{memberApplication}/approve',
    [
        FieldOfficerMemberApplicationController::class,
        'approve',
    ]
)
    ->whereUuid('memberApplication')
    ->name(
        'field-officer.member-applications.approve'
    );

Route::patch(
    '/field-officer/member-applications/{memberApplication}/reject',
    [
        FieldOfficerMemberApplicationController::class,
        'reject',
    ]
)
    ->whereUuid('memberApplication')
    ->name(
        'field-officer.member-applications.reject'
    );
