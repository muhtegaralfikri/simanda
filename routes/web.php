<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityDocumentController;
use App\Http\Controllers\ActivityExecutionController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\BudgetPlanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Master\BudgetYearController;
use App\Http\Controllers\Master\DocumentTypeController;
use App\Http\Controllers\Master\ExpenseTypeController;
use App\Http\Controllers\Master\FundingSourceController;
use App\Http\Controllers\Master\UnitController;
use App\Http\Controllers\Master\UserController;
use App\Http\Controllers\ProgramController;
use App\Http\Controllers\RealizationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\System\ActivityLogController;
use App\Http\Controllers\SystemHealthController;
use App\Http\Controllers\VerificationController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Guest Routes
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Analitik
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Profile & Password
    Route::get('/profile', [AuthController::class, 'profile'])->name('profile.show');
    Route::put('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->name('profile.password');

    /*
    |--------------------------------------------------------------------------
    | Tahap 6 — Modul Pusat Peringatan Internal
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/alerts')->name('admin.alerts.')->group(function () {
        Route::get('/', [AlertController::class, 'index'])->name('index');
        Route::post('/{alert}/read', [AlertController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [AlertController::class, 'markAllAsRead'])->name('read-all');
    });

    /*
    |--------------------------------------------------------------------------
    | Tahap 2 — Modul Perencanaan (Program, Kegiatan, RAB)
    |--------------------------------------------------------------------------
    */
    Route::resource('programs', ProgramController::class)->except(['create', 'edit']);
    Route::resource('activities', ActivityController::class);
    Route::post('/activities/{activity}/plan', [ActivityController::class, 'setPlanned'])->name('activities.plan');
    Route::post('/activities/{activity}/return-to-draft', [ActivityController::class, 'returnToDraft'])->name('activities.return-to-draft');
    Route::post('/activities/{activity}/cancel', [ActivityController::class, 'cancel'])->name('activities.cancel');

    // Budget Plan (RAB) Routes
    Route::post('/activities/{activity}/budget-plans', [BudgetPlanController::class, 'store'])->name('activities.budget-plans.store');
    Route::put('/budget-plans/{budgetPlan}', [BudgetPlanController::class, 'update'])->name('budget-plans.update');
    Route::delete('/budget-plans/{budgetPlan}', [BudgetPlanController::class, 'destroy'])->name('budget-plans.destroy');
    Route::get('/budget-plans', [BudgetPlanController::class, 'index'])->name('budget-plans.index');

    /*
    |--------------------------------------------------------------------------
    | Tahap 3 — Modul Pelaksanaan (Execution, Realization & Documents)
    |--------------------------------------------------------------------------
    */
    Route::post('/activities/{activity}/start', [ActivityExecutionController::class, 'start'])->name('activities.start');
    Route::post('/activities/{activity}/progress', [ActivityExecutionController::class, 'updateProgress'])->name('activities.progress.update');

    Route::get('/realizations', [RealizationController::class, 'index'])->name('realizations.index');
    Route::get('/realizations/progress', [RealizationController::class, 'progress'])->name('realizations.progress');
    Route::post('/activities/{activity}/realizations', [RealizationController::class, 'store'])->name('activities.realizations.store');
    Route::put('/realizations/{realization}', [RealizationController::class, 'update'])->name('realizations.update');
    Route::delete('/realizations/{realization}', [RealizationController::class, 'destroy'])->name('realizations.destroy');
    Route::post('/realizations/{realization}/submit', [RealizationController::class, 'submit'])->name('realizations.submit');

    Route::get('/documents', [ActivityDocumentController::class, 'index'])->name('documents.index');
    Route::post('/activities/{activity}/documents', [ActivityDocumentController::class, 'store'])->name('activities.documents.store');
    Route::post('/documents/{document}/replace', [ActivityDocumentController::class, 'replace'])->name('documents.replace');
    Route::delete('/documents/{document}', [ActivityDocumentController::class, 'destroy'])->name('documents.destroy');
    Route::get('/documents/{document}/download', [ActivityDocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [ActivityDocumentController::class, 'preview'])->name('documents.preview');

    /*
    |--------------------------------------------------------------------------
    | Tahap 4 — Modul Verifikasi & Revisi
    |--------------------------------------------------------------------------
    */
    Route::post('/activities/{activity}/submit-verification', [VerificationController::class, 'submit'])->name('activities.submit-verification');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/verifications', [VerificationController::class, 'index'])->name('verifications.index');
        Route::get('/verifications/{activity}', [VerificationController::class, 'show'])->name('verifications.show');
        Route::post('/verifications/{activity}/start', [VerificationController::class, 'startReview'])->name('verifications.start');
        Route::post('/verifications/{activity}/revision', [VerificationController::class, 'requestRevision'])->name('verifications.request-revision');
        Route::post('/verifications/{activity}/reject', [VerificationController::class, 'reject'])->name('verifications.reject');
        Route::post('/verifications/{activity}/close', [VerificationController::class, 'close'])->name('verifications.close');
    });

    Route::get('/verifications/incoming', [VerificationController::class, 'incoming'])->name('verifications.incoming');
    Route::get('/verifications/revisions', [VerificationController::class, 'revisions'])->name('verifications.revisions');
    Route::get('/verifications/history', [VerificationController::class, 'history'])->name('verifications.history');

    Route::post('/realizations/{realization}/verify', [VerificationController::class, 'verifyRealization'])->name('realizations.verify');
    Route::post('/documents/{document}/verify', [VerificationController::class, 'verifyDocument'])->name('documents.verify');

    /*
    |--------------------------------------------------------------------------
    | Tahap 5 — Pusat Laporan, PDF & Excel Exports
    |--------------------------------------------------------------------------
    */
    Route::prefix('admin/reports')->name('admin.reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');

        Route::get('/budget-summary', [ReportController::class, 'budget'])->name('budget.index');
        Route::get('/budget-summary/pdf', [ReportController::class, 'budgetPdf'])->name('budget.pdf');
        Route::get('/budget-summary/excel', [ReportController::class, 'budgetExcel'])->name('budget.excel');

        Route::get('/realizations', [ReportController::class, 'realization'])->name('realization.index');
        Route::get('/realizations/pdf', [ReportController::class, 'realizationPdf'])->name('realization.pdf');
        Route::get('/realizations/excel', [ReportController::class, 'realizationExcel'])->name('realization.excel');

        Route::get('/activities', [ReportController::class, 'activity'])->name('activity.index');
        Route::get('/activities/pdf', [ReportController::class, 'activityPdf'])->name('activity.pdf');
        Route::get('/activities/excel', [ReportController::class, 'activityExcel'])->name('activity.excel');

        Route::get('/progress', [ReportController::class, 'progress'])->name('progress.index');
        Route::get('/progress/pdf', [ReportController::class, 'progressPdf'])->name('progress.pdf');
        Route::get('/progress/excel', [ReportController::class, 'progressExcel'])->name('progress.excel');

        Route::get('/documents', [ReportController::class, 'documents'])->name('documents.index');
        Route::get('/documents/pdf', [ReportController::class, 'documentsPdf'])->name('documents.pdf');
        Route::get('/documents/excel', [ReportController::class, 'documentsExcel'])->name('documents.excel');

        Route::get('/verifications', [ReportController::class, 'verifications'])->name('verifications.index');
        Route::get('/verifications/pdf', [ReportController::class, 'verificationsPdf'])->name('verifications.pdf');
        Route::get('/verifications/excel', [ReportController::class, 'verificationsExcel'])->name('verifications.excel');

        Route::get('/monthly-absorption', [ReportController::class, 'absorption'])->name('absorption.index');
        Route::get('/monthly-absorption/pdf', [ReportController::class, 'absorptionPdf'])->name('absorption.pdf');
        Route::get('/monthly-absorption/excel', [ReportController::class, 'absorptionExcel'])->name('absorption.excel');

        Route::get('/remaining-budget', [ReportController::class, 'remainingBudget'])->name('remaining-budget.index');
        Route::get('/remaining-budget/pdf', [ReportController::class, 'remainingBudgetPdf'])->name('remaining-budget.pdf');
        Route::get('/remaining-budget/excel', [ReportController::class, 'remainingBudgetExcel'])->name('remaining-budget.excel');
    });

    /*
    |--------------------------------------------------------------------------
    | Tahap 6 — Modul System Status Health & Backup Management
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('admin/system')->name('admin.system.')->group(function () {
        Route::get('/health', [SystemHealthController::class, 'index'])->name('health');

        Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('/backups/run', [BackupController::class, 'run'])->name('backups.run');
        Route::post('/backups/{backup}/verify', [BackupController::class, 'verify'])->name('backups.verify');
    });

    // Logs (Admin & Pimpinan)
    Route::middleware('role:admin,pimpinan')->group(function () {
        Route::get('/system/logs', [ActivityLogController::class, 'index'])->name('system.logs.index');
    });

    /*
    |--------------------------------------------------------------------------
    | Administrator Master Data Routes
    |--------------------------------------------------------------------------
    */
    Route::middleware('role:admin')->prefix('master')->name('master.')->group(function () {
        Route::get('/budget-years', [BudgetYearController::class, 'index'])->name('budget-years.index');
        Route::post('/budget-years', [BudgetYearController::class, 'store'])->name('budget-years.store');
        Route::put('/budget-years/{budgetYear}', [BudgetYearController::class, 'update'])->name('budget-years.update');
        Route::post('/budget-years/{budgetYear}/toggle-active', [BudgetYearController::class, 'toggleActive'])->name('budget-years.toggle-active');
        Route::post('/budget-years/{budgetYear}/toggle-closed', [BudgetYearController::class, 'toggleClosed'])->name('budget-years.toggle-closed');

        Route::get('/units', [UnitController::class, 'index'])->name('units.index');
        Route::post('/units', [UnitController::class, 'store'])->name('units.store');
        Route::put('/units/{unit}', [UnitController::class, 'update'])->name('units.update');
        Route::post('/units/{unit}/toggle-active', [UnitController::class, 'toggleActive'])->name('units.toggle-active');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');

        Route::get('/funding-sources', [FundingSourceController::class, 'index'])->name('funding-sources.index');
        Route::post('/funding-sources', [FundingSourceController::class, 'store'])->name('funding-sources.store');
        Route::put('/funding-sources/{fundingSource}', [FundingSourceController::class, 'update'])->name('funding-sources.update');
        Route::post('/funding-sources/{fundingSource}/toggle-active', [FundingSourceController::class, 'toggleActive'])->name('funding-sources.toggle-active');

        Route::get('/expense-types', [ExpenseTypeController::class, 'index'])->name('expense-types.index');
        Route::post('/expense-types', [ExpenseTypeController::class, 'store'])->name('expense-types.store');
        Route::put('/expense-types/{expenseType}', [ExpenseTypeController::class, 'update'])->name('expense-types.update');
        Route::post('/expense-types/{expenseType}/toggle-active', [ExpenseTypeController::class, 'toggleActive'])->name('expense-types.toggle-active');

        Route::get('/document-types', [DocumentTypeController::class, 'index'])->name('document-types.index');
        Route::post('/document-types', [DocumentTypeController::class, 'store'])->name('document-types.store');
        Route::put('/document-types/{documentType}', [DocumentTypeController::class, 'update'])->name('document-types.update');
        Route::post('/document-types/{documentType}/toggle-active', [DocumentTypeController::class, 'toggleActive'])->name('document-types.toggle-active');
    });
});
