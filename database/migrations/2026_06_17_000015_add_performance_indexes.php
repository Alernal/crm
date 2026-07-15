<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // clients: búsquedas por usuario+estado y por nombre
        Schema::table('clients', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'clients_user_status_idx');
            $table->index(['user_id', 'name'],   'clients_user_name_idx');
        });

        // invoices: filtros por usuario+estado, por fecha y para la numeración
        Schema::table('invoices', function (Blueprint $table) {
            $table->index(['user_id', 'status'],     'invoices_user_status_idx');
            $table->index(['user_id', 'issue_date'],  'invoices_user_issue_date_idx');
            $table->index(['user_id', 'created_at'],  'invoices_user_created_at_idx');
            $table->index(['client_id', 'status'],    'invoices_client_status_idx');
        });

        // payments: suma de cobros por usuario y por mes
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['user_id', 'payment_date'], 'payments_user_date_idx');
            $table->index(['invoice_id'],              'payments_invoice_idx');
        });

        // tax_events: alertas por usuario+estado+fecha (3 queries en cada página)
        Schema::table('tax_events', function (Blueprint $table) {
            $table->index(['user_id', 'status', 'due_date'], 'tax_events_user_status_due_idx');
            $table->index(['user_id', 'due_date'],           'tax_events_user_due_idx');
            $table->index(['client_id'],                     'tax_events_client_idx');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex('clients_user_status_idx');
            $table->dropIndex('clients_user_name_idx');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropIndex('invoices_user_status_idx');
            $table->dropIndex('invoices_user_issue_date_idx');
            $table->dropIndex('invoices_user_created_at_idx');
            $table->dropIndex('invoices_client_status_idx');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex('payments_user_date_idx');
            $table->dropIndex('payments_invoice_idx');
        });

        Schema::table('tax_events', function (Blueprint $table) {
            $table->dropIndex('tax_events_user_status_due_idx');
            $table->dropIndex('tax_events_user_due_idx');
            $table->dropIndex('tax_events_client_idx');
        });
    }
};
