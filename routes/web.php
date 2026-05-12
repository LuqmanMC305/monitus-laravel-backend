<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\IncidentMapController;
use App\Http\Controllers\Api\AlertController;
use App\Http\Controllers\Admin\CommunityApprovalController;

// Redirect to login instead of welcome page
Route::redirect('/', '/register');

/*
Route::get('/', function () {
    return view('welcome');
});
*/

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Admin Dashboard
    Route::get('/dashboard', [IncidentMapController::class, 'dashboard'])->name('dashboard'); 
    // Incident Map Route
    Route::get('/incident-map', [IncidentMapController::class, 'index'])->name('incident.map');
    // Alert Controller
    Route::post('/api/send-alert', [AlertController::class, 'store']);
    // Resolve Alerts
    Route::patch('api/alerts/{id}/resolve', [IncidentMapController::class, 'resolve']);
    // Manage Alerts
    Route::get('/admin/manage-alerts', [IncidentMapController::class, 'manage'])->name('admin.manage-alerts');
    // Broadcast to Community 
    Route::post('/broadcast/{communityID}', [AlertController::class, 'broadcastToCommunity'])->name('community.broadcast');

    Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    // Page View
    Route::get('/admin/community-approvals', [CommunityApprovalController::class, 'index'])
         ->name('admin.community-approvals');

    // Approve Users
    Route::post('/admin/community-approvals/{user}/{community}/approve', [CommunityApprovalController::class, 'approve'])
         ->name('admin.community-approvals.approve');
    
    // Reject Users
    // Reject Action (Recommended to add this now)
    Route::post('/admin/community-approvals/{user}/{community}/reject', [CommunityApprovalController::class, 'reject'])
        ->name('admin.community-approvals.reject');

    Route::post('/admin/community-broadcast', [AlertController::class, 'broadcastToCommunity'])
    ->name('admin.community.broadcast');
});
});
