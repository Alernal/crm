<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FieldController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\ParameterController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TaxCalendarImportController;
use App\Http\Controllers\Admin\TaxDueDateController;
use App\Http\Controllers\Admin\TaxObligationController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {

    // --- Panel protegido (usa la sesión del CRM; requiere users.is_admin) ---
    Route::middleware('admin.auth')->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Módulos CRM
        Route::get   ('modules',         [ModuleController::class, 'index'])->name('modules.index');
        Route::patch ('modules/{module}/toggle', [ModuleController::class, 'toggle'])->name('modules.toggle');
        Route::patch ('modules/reorder', [ModuleController::class, 'reorder'])->name('modules.reorder');

        // Campos personalizados
        Route::get   ('fields',              [FieldController::class, 'index'])->name('fields.index');
        Route::get   ('fields/create',       [FieldController::class, 'create'])->name('fields.create');
        Route::post  ('fields',              [FieldController::class, 'store'])->name('fields.store');
        Route::get   ('fields/{field}/edit', [FieldController::class, 'edit'])->name('fields.edit');
        Route::patch ('fields/{field}',      [FieldController::class, 'update'])->name('fields.update');
        Route::delete('fields/{field}',      [FieldController::class, 'destroy'])->name('fields.destroy');
        Route::patch ('fields/{field}/toggle', [FieldController::class, 'toggle'])->name('fields.toggle');

        // Roles y permisos
        Route::get   ('roles',              [RoleController::class, 'index'])->name('roles.index');
        Route::get   ('roles/create',       [RoleController::class, 'create'])->name('roles.create');
        Route::post  ('roles',              [RoleController::class, 'store'])->name('roles.store');
        Route::get   ('roles/{role}/edit',  [RoleController::class, 'edit'])->name('roles.edit');
        Route::patch ('roles/{role}',       [RoleController::class, 'update'])->name('roles.update');
        Route::delete('roles/{role}',       [RoleController::class, 'destroy'])->name('roles.destroy');

        // Calendario tributario — importador Excel/CSV (reemplaza cada año las fechas de vencimiento)
        Route::get  ('tax-calendar/import',           [TaxCalendarImportController::class, 'showUpload'])      ->name('tax-calendar.import');
        Route::get  ('tax-calendar/import/template',  [TaxCalendarImportController::class, 'downloadTemplate'])->name('tax-calendar.import.template');
        Route::post ('tax-calendar/import',           [TaxCalendarImportController::class, 'upload'])          ->name('tax-calendar.import.submit');
        Route::get  ('tax-calendar/import/{taxCalendarImport}/review',  [TaxCalendarImportController::class, 'review']) ->name('tax-calendar.review');
        Route::post ('tax-calendar/import/{taxCalendarImport}/confirm', [TaxCalendarImportController::class, 'confirm'])->name('tax-calendar.confirm');

        // Parámetros globales
        Route::get  ('parameters',       [ParameterController::class, 'index'])->name('parameters.index');
        Route::patch('parameters/{param}',[ParameterController::class, 'update'])->name('parameters.update');

        // Obligaciones tributarias
        Route::get   ('tax-obligations',                 [TaxObligationController::class, 'index'])  ->name('tax-obligations.index');
        Route::get   ('tax-obligations/create',          [TaxObligationController::class, 'create']) ->name('tax-obligations.create');
        Route::post  ('tax-obligations',                 [TaxObligationController::class, 'store'])  ->name('tax-obligations.store');
        Route::get   ('tax-obligations/{taxObligation}/edit', [TaxObligationController::class, 'edit'])->name('tax-obligations.edit');
        Route::patch ('tax-obligations/{taxObligation}', [TaxObligationController::class, 'update']) ->name('tax-obligations.update');
        Route::delete('tax-obligations/{taxObligation}', [TaxObligationController::class, 'destroy'])->name('tax-obligations.destroy');

        // Fechas por dígito NIT
        Route::get ('tax-due-dates',        [TaxDueDateController::class, 'index'])      ->name('tax-due-dates.index');
        Route::post('tax-due-dates/save',   [TaxDueDateController::class, 'save'])       ->name('tax-due-dates.save');
        Route::post('tax-due-dates/delete-year', [TaxDueDateController::class, 'destroyYear'])->name('tax-due-dates.destroy-year');

        // Logs de auditoría
        Route::get('logs', [LogController::class, 'index'])->name('logs.index');
    });
});
