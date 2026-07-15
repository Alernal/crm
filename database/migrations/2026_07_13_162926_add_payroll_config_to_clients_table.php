<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->enum('payroll_periodicity', ['mensual', 'quincenal'])->nullable()->after('invoice_consecutive');
            $table->string('payroll_prefix', 20)->nullable()->after('payroll_periodicity');
            $table->unsignedInteger('payroll_consecutive')->default(0)->after('payroll_prefix');
            $table->boolean('payroll_pila_exempt')->default(false)->after('payroll_consecutive');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['payroll_periodicity', 'payroll_prefix', 'payroll_consecutive', 'payroll_pila_exempt']);
        });
    }
};
