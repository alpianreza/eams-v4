<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MasterData\AreaController;
use App\Http\Controllers\MasterData\AssetItemTypeController;
use App\Http\Controllers\MasterData\EmployeeController;
use App\Http\Controllers\MasterData\HolidayController;
use App\Http\Controllers\MasterData\InventoryCategoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'home' : 'login'));

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('home', HomeController::class)->name('home');

    /*
    | Master data (PHASE 2D).
    | View (GET)  : any authenticated user.
    | Manage (mutasi): admin/compliance (gate) + write permission (global write-guard).
    */
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
