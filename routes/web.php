<?php

use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CarteraController;
use App\Http\Controllers\CesantiaSettlementController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ContractSettlementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentEngine\CertificateController;
use App\Http\Controllers\DocumentEngine\GeneratedDocumentController;
use App\Http\Controllers\DocumentEngine\GeneratedProposalController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayrollPeriodController;
use App\Http\Controllers\PrimaSettlementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceCategoryController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TaxCalendarController;
use App\Http\Controllers\VacationControlController;
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
    Route::post('services/categories', [ServiceCategoryController::class, 'store'])->name('services.categories.store');
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

    Route::get   ('contract-settlements/create',                      [ContractSettlementController::class, 'create'])  ->name('contract-settlements.create');
    Route::get   ('contract-settlements',                             [ContractSettlementController::class, 'index'])   ->name('contract-settlements.index');
    Route::post  ('contract-settlements',                             [ContractSettlementController::class, 'store'])   ->name('contract-settlements.store');
    Route::get   ('contract-settlements/{contractSettlement}',        [ContractSettlementController::class, 'show'])    ->name('contract-settlements.show');
    Route::get   ('contract-settlements/{contractSettlement}/pdf',    [ContractSettlementController::class, 'pdf'])     ->middleware('throttle:10,1')->name('contract-settlements.pdf');
    Route::get   ('contract-settlements/{contractSettlement}/print',  [ContractSettlementController::class, 'printView'])->name('contract-settlements.print');
    Route::delete('contract-settlements/{contractSettlement}',        [ContractSettlementController::class, 'destroy'])->name('contract-settlements.destroy');

    Route::get   ('vacation-control',                          [VacationControlController::class, 'index'])                ->name('vacation-control.index');
    Route::get   ('vacation-control/calendar',                 [VacationControlController::class, 'calendar'])             ->name('vacation-control.calendar');
    Route::get   ('vacation-control/calendar/events',           [VacationControlController::class, 'calendarEvents'])       ->name('vacation-control.calendar.events');
    Route::get   ('vacation-control/suggest-business-days',     [VacationControlController::class, 'suggestBusinessDays'])  ->name('vacation-control.suggest-business-days');
    Route::get   ('vacation-control/{employee}',                [VacationControlController::class, 'show'])                ->name('vacation-control.show');
    Route::get   ('vacation-control/{employee}/pdf',            [VacationControlController::class, 'pdf'])                 ->middleware('throttle:10,1')->name('vacation-control.pdf');
    Route::get   ('vacation-control/{employee}/print',          [VacationControlController::class, 'printView'])           ->name('vacation-control.print');
    Route::patch ('vacation-control/{employee}/opening-balance',[VacationControlController::class, 'updateOpeningBalance']) ->name('vacation-control.opening-balance.update');
    Route::post  ('vacation-control/{employee}/periods',        [VacationControlController::class, 'storePeriod'])         ->name('vacation-control.periods.store');
    Route::patch ('vacation-control/periods/{period}',          [VacationControlController::class, 'updatePeriod'])        ->name('vacation-control.periods.update');
    Route::delete('vacation-control/periods/{period}',          [VacationControlController::class, 'destroyPeriod'])       ->name('vacation-control.periods.destroy');

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
    Route::post  ('tax-events/client/{client}/complete-occurrence', [TaxCalendarController::class, 'completeOccurrence'])->name('tax-events.complete-occurrence');
    Route::delete('tax-events/{taxEvent}',                  [TaxCalendarController::class, 'destroy'])       ->name('tax-events.destroy');
    Route::get   ('tax-events/client/{client}/calendar',    [TaxCalendarController::class, 'clientCalendar'])->name('tax-events.client-calendar');
    Route::get   ('tax-events/client/{client}/export-pdf',  [TaxCalendarController::class, 'exportPdf'])     ->name('tax-events.export-pdf');

    // Documentos personales (cédula/tarjeta profesional del contador — no confundir con el Motor Documental)
    Route::get   ('documents',                 [DocumentController::class, 'index'])  ->name('documents.index');
    Route::post  ('documents',                 [DocumentController::class, 'store'])  ->name('documents.store');
    Route::get   ('documents/{document}/file', [DocumentController::class, 'show'])   ->name('documents.show');
    Route::delete('documents/{document}',      [DocumentController::class, 'destroy'])->name('documents.destroy');

    // Motor Documental — Contratos y Propuestas (Fase 1: generación básica desde plantilla)
    Route::get   ('documentos/contratos',                [GeneratedDocumentController::class, 'index'])   ->name('documents.contracts.index');
    Route::get   ('documentos/contratos/nuevo',           [GeneratedDocumentController::class, 'create'])  ->name('documents.contracts.wizard');
    Route::post  ('documentos/contratos',                 [GeneratedDocumentController::class, 'store'])   ->name('documents.contracts.generate');
    Route::get   ('documentos/contratos/{document}',      [GeneratedDocumentController::class, 'show'])    ->name('documents.contracts.show');
    Route::get   ('documentos/contratos/{document}/pdf',  [GeneratedDocumentController::class, 'pdf'])     ->name('documents.contracts.pdf');
    Route::get   ('documentos/contratos/{document}/print',      [GeneratedDocumentController::class, 'printView']) ->name('documents.contracts.print');
    Route::post  ('documentos/contratos/{document}/send-email', [GeneratedDocumentController::class, 'sendEmail'])  ->name('documents.contracts.send_email');
    Route::delete('documentos/contratos/{document}',      [GeneratedDocumentController::class, 'destroy']) ->name('documents.contracts.destroy');

    // Sin ruta de índice propia — la lista de propuestas vive en el índice
    // compartido 'documents.contracts.index' (ya lista todos los GeneratedDocument).
    Route::get   ('documentos/propuestas/nueva',           [GeneratedProposalController::class, 'create'])  ->name('documents.proposals.wizard');
    Route::post  ('documentos/propuestas',                 [GeneratedProposalController::class, 'store'])   ->name('documents.proposals.generate');
    Route::get   ('documentos/propuestas/{document}',      [GeneratedProposalController::class, 'show'])    ->name('documents.proposals.show');
    Route::get   ('documentos/propuestas/{document}/pdf',  [GeneratedProposalController::class, 'pdf'])     ->name('documents.proposals.pdf');
    Route::get   ('documentos/propuestas/{document}/print',      [GeneratedProposalController::class, 'printView']) ->name('documents.proposals.print');
    Route::post  ('documentos/propuestas/{document}/send-email', [GeneratedProposalController::class, 'sendEmail'])  ->name('documents.proposals.send_email');
    Route::delete('documentos/propuestas/{document}',      [GeneratedProposalController::class, 'destroy']) ->name('documents.proposals.destroy');

    // Submódulo Certificados: Certificado de Ingresos con motor completo; Certificado
    // de Accionistas queda pendiente (ver CLAUDE.md).
    Route::get   ('documentos/certificados',                      [CertificateController::class, 'index'])   ->name('documents.certificates.index');
    Route::get   ('documentos/certificados/ingresos/nuevo',       [CertificateController::class, 'create'])  ->name('documents.certificates.wizard');
    Route::post  ('documentos/certificados/ingresos',              [CertificateController::class, 'store'])   ->name('documents.certificates.generate');
    Route::get   ('documentos/certificados/ingresos/{document}',  [CertificateController::class, 'show'])    ->name('documents.certificates.show');
    Route::get   ('documentos/certificados/ingresos/{document}/pdf',   [CertificateController::class, 'pdf'])     ->name('documents.certificates.pdf');
    Route::get   ('documentos/certificados/ingresos/{document}/print', [CertificateController::class, 'printView']) ->name('documents.certificates.print');
    Route::post  ('documentos/certificados/ingresos/{document}/send-email', [CertificateController::class, 'sendEmail'])  ->name('documents.certificates.send_email');
    Route::delete('documentos/certificados/ingresos/{document}',  [CertificateController::class, 'destroy']) ->name('documents.certificates.destroy');

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
    // Presupuestos (Ventas / Gastos Admón y Ventas / Flujo de Caja)
    Route::get    ('financial',                             [BudgetController::class, 'index'])         ->name('financial.index');
    Route::get    ('financial/cliente/{client}',           [BudgetController::class, 'clientBudgets']) ->name('financial.client');
    Route::get    ('financial/create',                     [BudgetController::class, 'create'])        ->name('financial.create');
    // Estados Financieros (ESF / ERI) — mismo controlador, tarjetas y formulario separados
    Route::get    ('financial/estados-financieros',                          [BudgetController::class, 'statementsIndex'])  ->name('financial.statements.index');
    Route::get    ('financial/estados-financieros/cliente/{client}',         [BudgetController::class, 'statementsClient']) ->name('financial.statements.client');
    Route::get    ('financial/estados-financieros/create',                   [BudgetController::class, 'statementsCreate']) ->name('financial.statements.create');
    Route::post   ('financial/estados-financieros',                          [BudgetController::class, 'statementsStore'])  ->name('financial.statements.store');
    Route::get    ('financial/estados-financieros/{budget}',                 [BudgetController::class, 'statementsShow'])   ->name('financial.statements.show');
    Route::get    ('financial/estados-financieros/{budget}/edit',            [BudgetController::class, 'statementsEdit'])   ->name('financial.statements.edit');
    Route::put    ('financial/estados-financieros/{budget}',                 [BudgetController::class, 'statementsUpdate']) ->name('financial.statements.update');
    Route::delete ('financial/estados-financieros/{budget}',                 [BudgetController::class, 'statementsDestroy'])->name('financial.statements.destroy');
    Route::patch  ('financial/criterios/{client}',          [BudgetController::class, 'updateRatioTargets']) ->name('financial.ratio_targets.update');
    Route::post   ('financial',                            [BudgetController::class, 'store'])         ->name('financial.store');
    Route::get    ('financial/{budget}',                   [BudgetController::class, 'show'])          ->name('financial.show');
    Route::get    ('financial/{budget}/edit',              [BudgetController::class, 'edit'])          ->name('financial.edit');
    Route::get    ('financial/{budget}/print',             [BudgetController::class, 'printView'])     ->name('financial.print');
    Route::get    ('financial/{budget}/pdf',               [BudgetController::class, 'pdf'])           ->name('financial.pdf');
    Route::get    ('financial/{budget}/dashboard',           [BudgetController::class, 'dashboard'])     ->name('financial.dashboard');
    Route::put    ('financial/{budget}',                   [BudgetController::class, 'update'])        ->name('financial.update');
    Route::delete ('financial/{budget}',                   [BudgetController::class, 'destroy'])       ->name('financial.destroy');
    Route::post   ('financial/{budget}/project',           [BudgetController::class, 'project'])       ->name('financial.project');
    Route::post   ('financial/{budget}/approve',           [BudgetController::class, 'approve'])       ->name('financial.approve');
    Route::post   ('financial/{budget}/update-value',      [BudgetController::class, 'updateValue'])   ->name('financial.update_value');
    Route::get    ('financial/datos/{client}',             [BudgetController::class, 'data'])          ->name('financial.data');
    Route::post   ('financial/datos/{client}',             [BudgetController::class, 'saveData'])      ->name('financial.save_data');
    Route::get    ('financial/{budget}/value-entries',                          [BudgetController::class, 'valueEntriesIndex'])      ->name('financial.value_entries.index');
    Route::post   ('financial/{budget}/value-entries',                          [BudgetController::class, 'valueEntriesSave'])       ->name('financial.value_entries.save');

    // Perfil
    Route::get   ('/profile',      [ProfileController::class, 'edit'])       ->name('profile.edit');
    Route::patch ('/profile',      [ProfileController::class, 'update'])     ->name('profile.update');
    Route::delete('/profile',      [ProfileController::class, 'destroy'])    ->name('profile.destroy');
    Route::delete('/profile/logo', [ProfileController::class, 'destroyLogo'])->name('profile.logo.destroy');
});

require __DIR__.'/auth.php';
