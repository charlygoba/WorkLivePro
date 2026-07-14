<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WebAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [WebAuthController::class, 'showLogin'])->name('login');
Route::post('/login', [WebAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
Route::post('/logout', [WebAuthController::class, 'logout'])->name('logout');
Route::get('/branding/icon', [DashboardController::class, 'brandIcon'])->name('branding.icon');

Route::middleware('worklive.web')->group(function () {
    Route::get('/legacy-dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/employees', [DashboardController::class, 'employees'])->name('employees');
    Route::post('/employees', [DashboardController::class, 'storeEmployee'])->name('employees.store');
    Route::get('/employees/{id}', [DashboardController::class, 'employee'])->name('employees.show');
    Route::put('/employees/{id}', [DashboardController::class, 'updateEmployee'])->name('employees.update');
    Route::post('/employees/{id}/sync', [DashboardController::class, 'requestEmployeeSync'])->name('employees.sync');
    Route::delete('/employees/{id}', [DashboardController::class, 'deleteWebEmployee'])->name('employees.delete');
    Route::put('/employees/{employeeId}/devices/{deviceId}', [DashboardController::class, 'updateDevice'])->name('employees.devices.update');
    Route::get('/reports', [DashboardController::class, 'reports'])->name('reports');
    Route::get('/reports/export', [DashboardController::class, 'exportReports'])->name('reports.export');
    Route::get('/reports/export/xlsx', [DashboardController::class, 'exportReportsXlsx'])->name('reports.export.xlsx');
    Route::get('/reports/export/pdf', [DashboardController::class, 'exportReportsPdf'])->name('reports.export.pdf');
    Route::get('/policies', [DashboardController::class, 'policies'])->name('policies');
    Route::post('/policies', [DashboardController::class, 'storePolicy'])->name('policies.store');
    Route::put('/policies/{id}', [DashboardController::class, 'updatePolicy'])->name('policies.update');
    Route::delete('/policies/{id}', [DashboardController::class, 'deletePolicy'])->name('policies.delete');
    Route::post('/policies/push', [DashboardController::class, 'pushPoliciesWeb'])->name('policies.push');
    Route::get('/hr/time-clock', [DashboardController::class, 'timeClock'])->name('time-clock');
    Route::get('/hr/time-clock/export', [DashboardController::class, 'exportTimeClock'])->name('time-clock.export');
    Route::get('/settings', [DashboardController::class, 'settings'])->name('settings');
    Route::post('/settings', [DashboardController::class, 'saveSettings'])->name('settings.save');
    Route::get('/settings/administrators', [DashboardController::class, 'administrators'])->name('settings.admins.index');
    Route::get('/settings/personalization', [DashboardController::class, 'personalization'])->name('settings.personalization');
    Route::post('/settings/personalization', [DashboardController::class, 'savePersonalization'])->name('settings.personalization.save');
    Route::post('/settings/admins', [DashboardController::class, 'addAdmin'])->name('settings.admins.store');
    Route::delete('/settings/admins/{email}', [DashboardController::class, 'removeAdmin'])->name('settings.admins.delete');
});
Route::get('/', fn () => redirect()->route('dashboard'))->name('home');
