<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->index(['client_id', 'status'], 'employees_client_status_idx');
            $table->index(['user_id', 'status'], 'employees_user_status_idx');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->index(['client_id', 'status'], 'payroll_periods_client_status_idx');
            $table->index(['user_id', 'start_date'], 'payroll_periods_user_start_date_idx');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->index(['payroll_period_id'], 'payrolls_period_idx');
            $table->index(['employee_id'], 'payrolls_employee_idx');
        });

        Schema::table('payroll_overtime_items', function (Blueprint $table) {
            $table->index(['payroll_id'], 'payroll_overtime_items_payroll_idx');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_client_status_idx');
            $table->dropIndex('employees_user_status_idx');
        });

        Schema::table('payroll_periods', function (Blueprint $table) {
            $table->dropIndex('payroll_periods_client_status_idx');
            $table->dropIndex('payroll_periods_user_start_date_idx');
        });

        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropIndex('payrolls_period_idx');
            $table->dropIndex('payrolls_employee_idx');
        });

        Schema::table('payroll_overtime_items', function (Blueprint $table) {
            $table->dropIndex('payroll_overtime_items_payroll_idx');
        });
    }
};
