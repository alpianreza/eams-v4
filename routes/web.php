<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Checklist\ChecklistController;
use App\Http\Controllers\Checklist\GridChecklistController;
use App\Http\Controllers\Compliance\ComplianceInventoryController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ItDevice\ItDeviceController;
use App\Http\Controllers\MasterData\AreaController;
use App\Http\Controllers\MasterData\AssetItemTypeController;
use App\Http\Controllers\MasterData\EmployeeController;
use App\Http\Controllers\MasterData\HolidayController;
use App\Http\Controllers\MasterData\InventoryCategoryController;
use App\Http\Controllers\Report\ComplianceReportController;
use App\Http\Controllers\Utility\UtilityLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'home' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('home', HomeController::class)->name('home');

    Route::get('files/{category}/{path}', [FileController::class, 'show'])->where('path', '.*')->name('files.show');

    // IT Device Monitoring (2J).
    Route::get('it/devices', [ItDeviceController::class, 'index'])->name('it.devices.index');

    // Boiler & Utility daily logs (2K) — index authed; write via global write-guard.
    Route::get('utility/{type}', [UtilityLogController::class, 'index'])->name('utility.index');
    Route::post('utility/{type}', [UtilityLogController::class, 'store'])->name('utility.store');
    Route::delete('utility/{type}/{id}', [UtilityLogController::class, 'destroy'])->name('utility.destroy');

    // Checklist — STANDARD (2F) & GRID (2G).
    Route::get('compliance/checklist/{inventory}/fill', [ChecklistController::class, 'fill'])->name('compliance.checklist.fill');
    Route::post('compliance/checklist/{inventory}', [ChecklistController::class, 'store'])->name('compliance.checklist.store');
    Route::get('compliance/checklist-grid/{itemType}', [GridChecklistController::class, 'show'])->name('compliance.checklist.grid');
    Route::post('compliance/checklist-grid/{itemType}/set', [GridChecklistController::class, 'set'])->name('compliance.checklist.grid.set');
    Route::post('compliance/checklist-grid/{itemType}/mark-all', [GridChecklistController::class, 'markAll'])->name('compliance.checklist.grid.mark-all');
    Route::post('compliance/checklist-grid/{itemType}/clear', [GridChecklistController::class, 'clear'])->name('compliance.checklist.grid.clear');

    // Compliance report PDF (2I) — GATE access-compliance-pdf (Q-008).
    Route::get('compliance/report/{inventory}/pdf', [ComplianceReportController::class, 'pdf'])
        ->name('compliance.report.pdf')->middleware('can:access-compliance-pdf');

    // Compliance Inventory (2E).
    Route::get('compliance/inventory', [ComplianceInventoryController::class, 'index'])->name('compliance.inventory.index');
    Route::get('compliance/inventory/create', [ComplianceInventoryController::class, 'create'])->name('compliance.inventory.create');
    Route::get('compliance/inventory/detail/{inventory}', [ComplianceInventoryController::class, 'show'])->name('compliance.inventory.detail');
    Route::post('compliance/inventory', [ComplianceInventoryController::class, 'store'])->name('compliance.inventory.store')->middleware('can:manage-inventory');
    Route::get('compliance/inventory/{inventory}/edit', [ComplianceInventoryController::class, 'edit'])->name('compliance.inventory.edit')->middleware('can:manage-inventory');
    Route::put('compliance/inventory/{inventory}', [ComplianceInventoryController::class, 'update'])->name('compliance.inventory.update')->middleware('can:manage-inventory');
    Route::delete('compliance/inventory/{inventory}', [ComplianceInventoryController::class, 'destroy'])->name('compliance.inventory.destroy')->middleware('can:manage-inventory');

    // Master data (2D).
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
