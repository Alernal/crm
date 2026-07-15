<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CesantiaSettlementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PrimaSettlementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TaxCalendarController;
use App\Http\Controllers\VirtualArchive\FileController as ArchiveFileController;
use App\Http\Controllers\VirtualArchive\FolderController as ArchiveFolderController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {

    // Clientes
    Route::get('clients/search', [ClientController::class, 'search'])->name('clients.search');
    Route::resource('clients', ClientController::class);

    // Servicios
    Route::resource('services', ServiceController::class);

    // Cuentas de cobro — PDF con throttle: máx 10 PDFs/min por usuario
    Route::resource('invoices', InvoiceController::class);
    Route::post('invoices/{invoice}/send-email', [InvoiceController::class, 'sendEmail'])
        ->middleware('throttle:10,1')
        ->name('invoices.send_email');
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])
        ->middleware('throttle:10,1')
        ->name('invoices.pdf');
    Route::get('invoices/{invoice}/pdf-estado', [InvoiceController::class, 'pdfWithStatement'])
        ->middleware('throttle:10,1')
        ->name('invoices.pdf_statement');
    Route::get('invoices/{invoice}/print', [InvoiceController::class, 'printView'])
        ->name('invoices.print');
    Route::get('invoices/{invoice}/print-estado', [InvoiceController::class, 'printWithStatement'])
        ->name('invoices.print_statement');

    // ─── NÓMINA ──────────────────────────────────────────────────────────────
    Route::resource('employees', EmployeeController::class);

    Route::get   ('payroll-periods',                        [PayrollPeriodController::class, 'index'])  ->name('payroll-periods.index');
    Route::post  ('payroll-periods',                        [PayrollPeriodController::class, 'store'])  ->name('payroll-periods.store');
    Route::get   ('payroll-periods/{payrollPeriod}',         [PayrollPeriodController::class, 'show'])   ->name('payroll-periods.show');
    Route::get   ('payroll-periods/{payrollPeriod}/pdf',     [PayrollPeriodController::class, 'pdf'])    ->middleware('throttle:10,1')->name('payroll-periods.pdf');
    Route::get   ('payroll-periods/{payrollPeriod}/print',   [PayrollPeriodController::class, 'printView'])->name('payroll-periods.print');
    Route::patch ('payroll-periods/{payrollPeriod}/close',   [PayrollPeriodController::class, 'close'])  ->name('payroll-periods.close');
    Route::delete('payroll-periods/{payrollPeriod}',         [PayrollPeriodController::class, 'destroy'])->name('payroll-periods.destroy');

    Route::patch ('payrolls/{payroll}',                [PayrollController::class, 'update'])        ->name('payrolls.update');
    Route::put   ('payrolls/{payroll}/overtime',       [PayrollController::class, 'updateOvertime']) ->name('payrolls.overtime.update');
    Route::get   ('payrolls/{payroll}/pdf',            [PayrollController::class, 'pdf'])           ->middleware('throttle:10,1')->name('payrolls.pdf');
    Route::get   ('payrolls/{payroll}/print',          [PayrollController::class, 'printView'])     ->name('payrolls.print');
    Route::post  ('payrolls/{payroll}/send-email',     [PayrollController::class, 'sendEmail'])     ->middleware('throttle:10,1')->name('payrolls.send_email');

    Route::get   ('prima-settlements',                        [PrimaSettlementController::class, 'index'])  ->name('prima-settlements.index');
    Route::post  ('prima-settlements',                        [PrimaSettlementController::class, 'store'])  ->name('prima-settlements.store');
    Route::get   ('prima-settlements/{primaSettlement}',       [PrimaSettlementController::class, 'show'])   ->name('prima-settlements.show');
    Route::get   ('prima-settlements/{primaSettlement}/pdf',   [PrimaSettlementController::class, 'pdf'])    ->middleware('throttle:10,1')->name('prima-settlements.pdf');
    Route::get   ('prima-settlements/{primaSettlement}/print', [PrimaSettlementController::class, 'printView'])->name('prima-settlements.print');
    Route::delete('prima-settlements/{primaSettlement}',       [PrimaSettlementController::class, 'destroy'])->name('prima-settlements.destroy');

    Route::get   ('cesantia-settlements',                        [CesantiaSettlementController::class, 'index'])  ->name('cesantia-settlements.index');
    Route::post  ('cesantia-settlements',                        [CesantiaSettlementController::class, 'store'])  ->name('cesantia-settlements.store');
    Route::get   ('cesantia-settlements/{cesantiaSettlement}',       [CesantiaSettlementController::class, 'show'])   ->name('cesantia-settlements.show');
    Route::get   ('cesantia-settlements/{cesantiaSettlement}/pdf',   [CesantiaSettlementController::class, 'pdf'])    ->middleware('throttle:10,1')->name('cesantia-settlements.pdf');
    Route::get   ('cesantia-settlements/{cesantiaSettlement}/print', [CesantiaSettlementController::class, 'printView'])->name('cesantia-settlements.print');
    Route::delete('cesantia-settlements/{cesantiaSettlement}',       [CesantiaSettlementController::class, 'destroy'])->name('cesantia-settlements.destroy');

    // Cartera
    Route::get('cartera',                              [CarteraController::class, 'index'])             ->name('cartera.index');
    Route::get('cartera/cliente/{client}',             [CarteraController::class, 'client'])             ->name('cartera.client');
    Route::get ('cartera/cliente/{client}/estado',      [CarteraController::class, 'printStatement'])     ->name('cartera.client_statement');
    Route::post('cartera/cliente/{client}/send-email', [CarteraController::class, 'sendStatementEmail'])->middleware('throttle:10,1')->name('cartera.send_statement');
    Route::get('cartera/{invoice}',                    [CarteraController::class, 'show'])               ->name('cartera.show');

    // Pagos
    Route::post  ('invoices/{invoice}/payments', [PaymentController::class, 'store'])  ->name('payments.store');
    Route::delete('payments/{payment}',          [PaymentController::class, 'destroy'])->name('payments.destroy');
    Route::get   ('payments/{payment}/receipt',  [PaymentController::class, 'receipt'])->name('payments.receipt');

    // Calendario Tributario
    Route::get   ('tax-events',                            [TaxCalendarController::class, 'index'])         ->name('tax-events.index');
    Route::get   ('tax-events/client/{client}',             [TaxCalendarController::class, 'show'])          ->name('tax-events.show');
    Route::get   ('tax-events/client/{client}/events',      [TaxCalendarController::class, 'events'])        ->middleware('throttle:60,1')->name('tax-events.events');
    Route::post  ('tax-events/client/{client}',             [TaxCalendarController::class, 'store'])         ->name('tax-events.store');
    Route::post  ('tax-events/client/{client}/ica',         [TaxCalendarController::class, 'storeICA'])      ->name('tax-events.ica');
    Route::patch ('tax-events/{taxEvent}',                  [TaxCalendarController::class, 'update'])        ->name('tax-events.update');
    Route::patch ('tax-events/{taxEvent}/complete',         [TaxCalendarController::class, 'complete'])      ->name('tax-events.complete');
    Route::delete('tax-events/{taxEvent}',                  [TaxCalendarController::class, 'destroy'])       ->name('tax-events.destroy');
    Route::get   ('tax-events/client/{client}/calendar',    [TaxCalendarController::class, 'clientCalendar'])->name('tax-events.client-calendar');
    Route::get   ('tax-events/client/{client}/export-pdf',  [TaxCalendarController::class, 'exportPdf'])     ->name('tax-events.export-pdf');

    // Documentos
    Route::get   ('documents',                 [DocumentController::class, 'index'])  ->name('documents.index');
    Route::post  ('documents',                 [DocumentController::class, 'store'])  ->name('documents.store');
    Route::get   ('documents/{document}/file', [DocumentController::class, 'show'])   ->name('documents.show');
    Route::delete('documents/{document}',      [DocumentController::class, 'destroy'])->name('documents.destroy');

    // ─── ARCHIVO VIRTUAL ─────────────────────────────────────────────────────
    Route::get   ('clients/{client}/archivo',                       [ArchiveFileController::class, 'index'])    ->name('archive.files.index');
    Route::post  ('clients/{client}/archivo/files',                 [ArchiveFileController::class, 'store'])    ->name('archive.files.store');
    Route::get   ('clients/{client}/archivo/files/{file}/download', [ArchiveFileController::class, 'download'])->name('archive.files.download');
    Route::patch ('clients/{client}/archivo/files/{file}',          [ArchiveFileController::class, 'update'])   ->name('archive.files.update');
    Route::patch ('clients/{client}/archivo/files/{file}/move',     [ArchiveFileController::class, 'move'])     ->name('archive.files.move');
    Route::delete('clients/{client}/archivo/files/{file}',          [ArchiveFileController::class, 'destroy'])  ->name('archive.files.destroy');
    Route::post  ('clients/{client}/archivo/folders',               [ArchiveFolderController::class, 'store'])  ->name('archive.folders.store');
    Route::patch ('clients/{client}/archivo/folders/{folder}',      [ArchiveFolderController::class, 'update']) ->name('archive.folders.update');
    Route::delete('clients/{client}/archivo/folders/{folder}',      [ArchiveFolderController::class, 'destroy'])->name('archive.folders.destroy');

    // ─── MÓDULO FINANCIERO ───────────────────────────────────────────────────
    Route::get    ('financial',                             [BudgetController::class, 'index'])         ->name('financial.index');
    Route::get    ('financial/cliente/{client}',           [BudgetController::class, 'clientBudgets']) ->name('financial.client');
    Route::get    ('financial/create',                     [BudgetController::class, 'create'])        ->name('financial.create');
    Route::post   ('financial',                            [BudgetController::class, 'store'])         ->name('financial.store');
    Route::get    ('financial/{budget}',                   [BudgetController::class, 'show'])          ->name('financial.show');
    Route::get    ('financial/{budget}/edit',              [BudgetController::class, 'edit'])          ->name('financial.edit');
    Route::get    ('financial/{budget}/print',             [BudgetController::class, 'printView'])     ->name('financial.print');
    Route::get    ('financial/{budget}/pdf',               [BudgetController::class, 'pdf'])           ->name('financial.pdf');
    Route::put    ('financial/{budget}',                   [BudgetController::class, 'update'])        ->name('financial.update');
    Route::delete ('financial/{budget}',                   [BudgetController::class, 'destroy'])       ->name('financial.destroy');
    Route::post   ('financial/{budget}/project',           [BudgetController::class, 'project'])       ->name('financial.project');
    Route::post   ('financial/{budget}/update-value',      [BudgetController::class, 'updateValue'])   ->name('financial.update_value');
    Route::get    ('financial/variables/{client}',         [BudgetController::class, 'variables'])     ->name('financial.variables');
    Route::post   ('financial/variables/{client}',         [BudgetController::class, 'saveVariables']) ->name('financial.save_variables');

    // Perfil
    Route::get   ('/profile',      [ProfileController::class, 'edit'])       ->name('profile.edit');
    Route::patch ('/profile',      [ProfileController::class, 'update'])     ->name('profile.update');
    Route::delete('/profile',      [ProfileController::class, 'destroy'])    ->name('profile.destroy');
    Route::delete('/profile/logo', [ProfileController::class, 'destroyLogo'])->name('profile.logo.destroy');
});

require __DIR__.'/auth.php';
