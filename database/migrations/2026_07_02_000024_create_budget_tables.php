<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Variables macroeconómicas por cliente
        Schema::create('budget_client_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->decimal('ipc', 5, 2)->default(6.77);
            $table->decimal('inflation', 5, 2)->default(5.00);
            $table->decimal('smmlv_increase', 5, 2)->default(9.54);
            $table->decimal('sales_growth', 5, 2)->default(10.00);
            $table->decimal('sales_growth_monthly', 5, 2)->default(0.80);
            $table->decimal('new_clients_pct', 5, 2)->default(5.00);
            $table->decimal('payroll_growth', 5, 2)->default(9.54);
            $table->decimal('rent_growth', 5, 2)->default(6.77);
            $table->decimal('utilities_growth', 5, 2)->default(8.00);
            $table->decimal('purchases_growth', 5, 2)->default(6.00);
            $table->decimal('interest_rate', 5, 2)->default(13.50);
            $table->decimal('services_growth', 5, 2)->default(7.00);
            $table->json('custom_variables')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'client_id']);
        });

        // Encabezado del presupuesto
        Schema::create('budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['ventas', 'gastos', 'compras', 'flujo_caja', 'nomina']);
            $table->unsignedSmallInteger('base_year');
            $table->enum('period_type', ['annual', 'quarterly', 'monthly'])->default('annual');
            $table->unsignedTinyInteger('periods_count')->default(3);
            $table->enum('status', ['draft', 'projected', 'final'])->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // Secciones (agrupadores de filas)
        Schema::create('budget_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Filas (rubros) del presupuesto
        Schema::create('budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('section_id')->constrained('budget_sections')->cascadeOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->enum('projection_driver', [
                'ipc', 'inflation', 'smmlv', 'sales_growth', 'sales_growth_monthly',
                'payroll_growth', 'rent_growth', 'utilities_growth', 'purchases_growth',
                'interest_rate', 'services_growth', 'fixed', 'manual', 'custom_pct',
            ])->default('ipc');
            $table->decimal('custom_rate', 5, 2)->nullable();
            $table->boolean('is_subtotal')->default(false);
            $table->boolean('sign_negative')->default(false);
            $table->timestamps();
        });

        // Valores por período
        Schema::create('budget_values', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('line_id')->constrained('budget_lines')->cascadeOnDelete();
            $table->string('period_label');
            $table->unsignedTinyInteger('period_index');
            $table->decimal('value', 20, 2)->default(0);
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();
            $table->unique(['line_id', 'period_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_values');
        Schema::dropIfExists('budget_lines');
        Schema::dropIfExists('budget_sections');
        Schema::dropIfExists('budgets');
        Schema::dropIfExists('budget_client_variables');
    }
};
