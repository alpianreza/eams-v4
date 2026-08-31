<?php

use App\Livewire\Compliance\InventoryIndex;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\LoginSessionController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Calendar\CalendarController;
use App\Http\Controllers\Checklist\ChecklistController;
use App\Http\Controllers\Checklist\GridChecklistController;
use App\Http\Controllers\Compliance\ChecklistMasterController;
use App\Http\Controllers\Compliance\ComplianceInventoryController;
use App\Http\Controllers\Compliance\PrintController;
use App\Http\Controllers\Dashboard\DashboardController;
use App\Http\Controllers\Ems\EmsReportController;
use App\Http\Controllers\Evidence\EvidenceController;
use App\Http\Controllers\Fdm\FdmDataCollectionController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItAsset\ITAssetController;
use App\Http\Controllers\ItDevice\ItDeviceController;
use App\Http\Controllers\MasterData\AreaController;
use App\Http\Controllers\MasterData\AssetItemTypeController;
use App\Http\Controllers\MasterData\EmployeeController;
use App\Http\Controllers\MasterData\HolidayController;
use App\Http\Controllers\MasterData\InventoryCategoryController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Patrol\PatrolController;
use App\Http\Controllers\Progress\ProgressController;
use App\Http\Controllers\Questionnaire\PublicQuestionnaireController;
use App\Http\Controllers\Questionnaire\QuestionnaireController;
use App\Http\Controllers\Ranking\RankingController;
use App\Http\Controllers\Report\ComplianceReportController;
use App\Http\Controllers\SelfServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Thermal\ThermalImagingController;
use App\Http\Controllers\Utility\UtilityLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'home' : 'login'));

Route::get('kuesioner/{questionnaire}', [PublicQuestionnaireController::class, 'fill'])->name('kuesioner.fill');
Route::post('kuesioner/{questionnaire}/kirim', [PublicQuestionnaireController::class, 'submit'])->name('kuesioner.submit');
Route::get('kuesioner/{questionnaire}/selesai', [PublicQuestionnaireController::class, 'thanks'])->name('kuesioner.thanks');

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('home', HomeController::class)->name('home');
    Route::get('files/{category}/{path}', [FileController::class, 'show'])->where('path', '.*')->name('files.show');

    Route::get('settings/password', [SelfServiceController::class, 'editPassword'])->name('self.password.edit');
    Route::post('settings/password', [SelfServiceController::class, 'updatePassword'])->name('self.password.update');

    Route::get('settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('settings/company', [SettingsController::class, 'storeCompany'])->name('settings.company');
    Route::post('settings/email', [SettingsController::class, 'storeEmail'])->name('settings.email');
    Route::post('settings/whatsapp', [SettingsController::class, 'storeWhatsApp'])->name('settings.whatsapp');
    Route::post('settings/contact', [SettingsController::class, 'storeContact'])->name('settings.contact');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');

    // User management (admin-only).
    Route::middleware('can:manage-users')->group(function () {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::post('users/roles', [UserController::class, 'storeRole'])->name('users.roles.store');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
        Route::post('users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    });

    // Monitoring layer (§7).
    Route::get('compliance/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
    Route::get('compliance/progress/export', [ProgressController::class, 'export'])->name('progress.export');
    Route::post('compliance/progress/{user}/remind', [ProgressController::class, 'remind'])->name('progress.remind')->middleware('can:write');
    Route::get('compliance/progress', [ProgressController::class, 'index'])->name('progress.index');
    Route::get('compliance/evidence', [EvidenceController::class, 'index'])->name('evidence.index');
    Route::put('compliance/evidence/{log}/followup', [EvidenceController::class, 'updateFollowup'])->name('evidence.followup');
    Route::get('compliance/ranking', [RankingController::class, 'index'])->name('ranking.index');

    Route::get('it/devices', [ItDeviceController::class, 'index'])->name('it.devices.index');

    // IT Assets (§8).
    Route::get('it-assets', [ITAssetController::class, 'index'])->name('it-assets.index');
    Route::post('it-assets', [ITAssetController::class, 'store'])->name('it-assets.store');
    Route::get('it-assets/{asset}', [ITAssetController::class, 'detail'])->name('it-assets.detail');
    Route::put('it-assets/{asset}', [ITAssetController::class, 'update'])->name('it-assets.update');
    Route::post('it-assets/{asset}/assign', [ITAssetController::class, 'assign'])->name('it-assets.assign');
    Route::post('it-assets/{asset}/return', [ITAssetController::class, 'returnAsset'])->name('it-assets.return');

    // Admin system tools (§19 butir 15): audit logs, login sessions, backups — admin-only.
    Route::middleware('can:manage-system')->prefix('admin')->name('admin.')->group(function () {
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('login-sessions', [LoginSessionController::class, 'index'])->name('login-sessions.index');
        Route::post('login-sessions/{session}/end', [LoginSessionController::class, 'end'])->name('login-sessions.end');
        Route::get('backups', [BackupController::class, 'index'])->name('backups.index');
        Route::post('backups', [BackupController::class, 'store'])->name('backups.store');
        Route::post('backups/prune', [BackupController::class, 'prune'])->name('backups.prune');
    });

    Route::get('compliance/calendar', \App\Livewire\Calendar\Index::class)->name('calendar.index');
    Route::post('compliance/calendar', [CalendarController::class, 'store'])->name('calendar.store');
    Route::delete('compliance/calendar/{event}', [CalendarController::class, 'destroy'])->name('calendar.destroy');

    Route::get('ems/{category}', [EmsReportController::class, 'index'])->name('ems.index');
    Route::post('ems/{category}/entry', [EmsReportController::class, 'saveEntry'])->name('ems.entry.save');
    Route::post('ems/{category}/year', [EmsReportController::class, 'saveYear'])->name('ems.year.save');

    Route::get('fdm-data-collection', [FdmDataCollectionController::class, 'index'])->name('fdm.index');
    Route::post('fdm-data-collection/entry', [FdmDataCollectionController::class, 'saveEntry'])->name('fdm.entry.save');

    Route::get('compliance/thermal-imaging', [ThermalImagingController::class, 'index'])->name('thermal.index');
    Route::post('compliance/thermal-imaging', [ThermalImagingController::class, 'store'])->name('thermal.store');
    Route::get('compliance/thermal-imaging/{report}', [ThermalImagingController::class, 'show'])->name('thermal.show');
    Route::post('compliance/thermal-imaging/{report}/items', [ThermalImagingController::class, 'addItem'])->name('thermal.items.store');

    Route::get('patrol', [PatrolController::class, 'index'])->name('patrol.index');
    Route::post('patrol/sessions/start', [PatrolController::class, 'start'])->name('patrol.start');
    Route::get('patrol/sessions/{session}', [PatrolController::class, 'show'])->name('patrol.session');
    Route::post('patrol/sessions/{session}/scan', [PatrolController::class, 'scan'])->name('patrol.scan');
    Route::post('patrol/sessions/{session}/cancel', [PatrolController::class, 'cancel'])->name('patrol.cancel');

    Route::get('utility/{type}', [UtilityLogController::class, 'index'])->name('utility.index');
    Route::post('utility/{type}', [UtilityLogController::class, 'store'])->name('utility.store');
    Route::delete('utility/{type}/{id}', [UtilityLogController::class, 'destroy'])->name('utility.destroy');

    Route::get('compliance/questionnaires', [QuestionnaireController::class, 'index'])->name('questionnaire.index');
    Route::post('compliance/questionnaires', [QuestionnaireController::class, 'store'])->name('questionnaire.store');
    Route::get('compliance/questionnaires/{questionnaire}', [QuestionnaireController::class, 'show'])->name('questionnaire.show');
    Route::post('compliance/questionnaires/{questionnaire}/questions', [QuestionnaireController::class, 'addQuestion'])->name('questionnaire.questions.store');

    Route::get('compliance/checklist/{inventory}/fill', [ChecklistController::class, 'fill'])->name('compliance.checklist.fill');
    Route::post('compliance/checklist/{inventory}', [ChecklistController::class, 'store'])->name('compliance.checklist.store');
    Route::get('compliance/checklist-grid/{itemType}', [GridChecklistController::class, 'show'])->name('compliance.checklist.grid');
    Route::post('compliance/checklist-grid/{itemType}/set', [GridChecklistController::class, 'set'])->name('compliance.checklist.grid.set');
    Route::post('compliance/checklist-grid/{itemType}/mark-all', [GridChecklistController::class, 'markAll'])->name('compliance.checklist.grid.mark-all');
    Route::post('compliance/checklist-grid/{itemType}/clear', [GridChecklistController::class, 'clear'])->name('compliance.checklist.grid.clear');

    // Checklist master (3-level: kategori -> item type -> pertanyaan).
    Route::get('compliance/checklist-master', [ChecklistMasterController::class, 'index'])->name('checklist-master.index');
    Route::get('compliance/checklist-master/category/{category}', [ChecklistMasterController::class, 'category'])->name('checklist-master.category');
    Route::get('compliance/checklist-master/item/{itemType}', [ChecklistMasterController::class, 'item'])->name('checklist-master.item');
    Route::post('compliance/checklist-master/item/{itemType}/question', [ChecklistMasterController::class, 'storeQuestion'])->name('checklist-master.question.store');
    Route::put('compliance/checklist-master/question/{master}', [ChecklistMasterController::class, 'updateQuestion'])->name('checklist-master.question.update');
    Route::delete('compliance/checklist-master/question/{master}', [ChecklistMasterController::class, 'destroyQuestion'])->name('checklist-master.question.destroy');
    Route::post('compliance/checklist-master/item/{itemType}/frequency', [ChecklistMasterController::class, 'updateFrequency'])->name('checklist-master.frequency');

    // Print center.
    Route::middleware('can:access-print-center')->group(function () {
        Route::get('compliance/print', [PrintController::class, 'index'])->name('print.index');
        Route::get('compliance/print/item', [PrintController::class, 'item'])->name('print.item');
        Route::get('compliance/print/inventory/{itemType}', [PrintController::class, 'inventoryByType'])->name('print.inventory-by-type');
        Route::get('compliance/print/batch', [PrintController::class, 'batch'])->name('print.batch');
        Route::get('compliance/print/batch/preview', [PrintController::class, 'batchPreview'])->name('print.batch-preview');
    });

    Route::get('compliance/report/{inventory}/pdf', [ComplianceReportController::class, 'pdf'])
        ->name('compliance.report.pdf')->middleware('can:access-compliance-pdf');

    // Full-page Livewire component: owns list filter/search/pagination state (docs 20 par.24).
    // Business rules (store/detail/update/destroy) remain in ComplianceInventoryController.
    Route::get('compliance/inventory', InventoryIndex::class)->name('compliance.inventory.index');
    Route::get('compliance/inventory/create', [ComplianceInventoryController::class, 'create'])->name('compliance.inventory.create');
    Route::get('compliance/inventory/detail/{inventory}', [ComplianceInventoryController::class, 'show'])->name('compliance.inventory.detail');
    Route::post('compliance/inventory', [ComplianceInventoryController::class, 'store'])->name('compliance.inventory.store')->middleware('can:manage-inventory');
    Route::get('compliance/inventory/{inventory}/edit', [ComplianceInventoryController::class, 'edit'])->name('compliance.inventory.edit')->middleware('can:manage-inventory');
    Route::put('compliance/inventory/{inventory}', [ComplianceInventoryController::class, 'update'])->name('compliance.inventory.update')->middleware('can:manage-inventory');
    Route::delete('compliance/inventory/{inventory}', [ComplianceInventoryController::class, 'destroy'])->name('compliance.inventory.destroy')->middleware('can:manage-inventory');

    Route::prefix('master-data')->name('master-data.')->group(function () {
        Route::get('areas', [AreaController::class, 'index'])->name('areas.index');
        Route::get('categories', [InventoryCategoryController::class, 'index'])->name('categories.index');
        Route::get('item-types', [AssetItemTypeController::class, 'index'])->name('item-types.index');
        Route::get('holidays', [HolidayController::class, 'index'])->name('holidays.index');
        Route::get('employees', [EmployeeController::class, 'index'])->name('employees.index');

        Route::middleware('can:manage-master-data')->group(function () {
            Route::post('areas', [AreaController::class, 'store'])->name('areas.store');
            Route::put('areas/{area}', [AreaController::class, 'update'])->name('areas.update');
            Route::delete('areas/{area}', [AreaController::class, 'destroy'])->name('areas.destroy');
            Route::post('categories', [InventoryCategoryController::class, 'store'])->name('categories.store');
            Route::put('categories/{category}', [InventoryCategoryController::class, 'update'])->name('categories.update');
            Route::delete('categories/{category}', [InventoryCategoryController::class, 'destroy'])->name('categories.destroy');
            Route::post('item-types', [AssetItemTypeController::class, 'store'])->name('item-types.store');
            Route::put('item-types/{itemType}', [AssetItemTypeController::class, 'update'])->name('item-types.update');
            Route::delete('item-types/{itemType}', [AssetItemTypeController::class, 'destroy'])->name('item-types.destroy');
            Route::post('holidays', [HolidayController::class, 'store'])->name('holidays.store');
            Route::put('holidays/{holiday}', [HolidayController::class, 'update'])->name('holidays.update');
            Route::delete('holidays/{holiday}', [HolidayController::class, 'destroy'])->name('holidays.destroy');
            Route::post('employees', [EmployeeController::class, 'store'])->name('employees.store');
            Route::put('employees/{employee}', [EmployeeController::class, 'update'])->name('employees.update');
            Route::delete('employees/{employee}', [EmployeeController::class, 'destroy'])->name('employees.destroy');
        });
    });
});
