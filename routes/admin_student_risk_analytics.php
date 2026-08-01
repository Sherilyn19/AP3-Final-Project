<?php

use App\Http\Controllers\Admin\StudentRiskAnalyticsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Student Risk Analytics Routes
| routes/admin_student_risk_analytics.php
|--------------------------------------------------------------------------
|
| Kept in a separate route file to avoid editing the existing large web.php
| too much. The controller still checks is_super_admin.
|
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::prefix('student-risk-analytics')
            ->name('student-risk-analytics.')
            ->group(function () {
                Route::get('/', [StudentRiskAnalyticsController::class, 'index'])->name('index');
                Route::get('/data', [StudentRiskAnalyticsController::class, 'data'])->name('data');
                Route::get('/dashboard-data', [StudentRiskAnalyticsController::class, 'dashboardData'])->name('dashboard-data');
                Route::get('/report-data', [StudentRiskAnalyticsController::class, 'reportData'])->name('report-data');
                Route::get('/export-csv', [StudentRiskAnalyticsController::class, 'exportCsv'])->name('export-csv');
                Route::get('/export-pdf', [StudentRiskAnalyticsController::class, 'exportPdf'])->name('export-pdf');
            });
    });
