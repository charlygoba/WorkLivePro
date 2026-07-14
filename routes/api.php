<?php

use App\Http\Controllers\WorkLiveController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WebAuthController;
use App\Http\Controllers\DashboardApiController;

Route::get('/health', [WorkLiveController::class, 'health'])->middleware('throttle:60,1');
Route::post('/agent/activate', [WorkLiveController::class, 'activate'])->middleware('throttle:10,1');
// Contrato consumido por el Tracker instalado: entrega la política efectiva del empleado.
Route::get('/agent/policy', [WorkLiveController::class, 'policy'])->middleware('throttle:120,1');
Route::get('/agent/policy-version', [WorkLiveController::class, 'policyVersion'])->middleware('throttle:120,1');
Route::post('/auth/login', [WebAuthController::class, 'apiLogin'])->middleware(['web', 'throttle:10,1']);
Route::middleware(['throttle:240,1', 'agent'])->group(function () {
    Route::post('/agent/event', [WorkLiveController::class, 'event']);
    Route::post('/agent/events', [WorkLiveController::class, 'events']);
    Route::get('/agent/sync-request', [WorkLiveController::class, 'syncRequest']);
    Route::post('/agent/sync-request/{id}/complete', [WorkLiveController::class, 'completeSyncRequest']);
});
Route::middleware(['web', 'worklive.web', 'throttle:120,1'])->group(function () {
    Route::get('/employees', [WorkLiveController::class, 'employees']);
    Route::get('/employees/{id}', [WorkLiveController::class, 'employee']);
    Route::post('/admin/employees', [WorkLiveController::class, 'saveEmployee']);
    Route::put('/admin/employees/{id}', [WorkLiveController::class, 'saveEmployee']);
    Route::delete('/admin/employees/{id}', [WorkLiveController::class, 'deleteEmployee']);
    Route::get('/settings', [DashboardApiController::class, 'settings']);
    Route::put('/settings', [DashboardApiController::class, 'saveSettings']);
    Route::get('/admins', [DashboardApiController::class, 'admins']);
    Route::post('/admins', [DashboardApiController::class, 'addAdmin']);
    Route::delete('/admins/{email}', [DashboardApiController::class, 'removeAdmin']);
    Route::get('/events', [DashboardApiController::class, 'events']);
    Route::get('/summaries', [DashboardApiController::class, 'summaries']);
    Route::get('/policy-profiles', [DashboardApiController::class, 'policies']);
    Route::post('/policy-profiles', [DashboardApiController::class, 'savePolicy']);
    Route::put('/policy-profiles/{id}', [DashboardApiController::class, 'savePolicy']);
    Route::delete('/policy-profiles/{id}', [DashboardApiController::class, 'deletePolicy']);
    Route::put('/policy-profiles/{id}/members', [DashboardApiController::class, 'members']);
    Route::post('/policies/push', [DashboardApiController::class, 'pushPolicies']);
});
