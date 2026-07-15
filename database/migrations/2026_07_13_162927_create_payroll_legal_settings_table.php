<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_legal_settings', function (Blueprint $table) {
            $table->id();
            $table->date('effective_from')->unique();

            $table->decimal('smlv', 14, 2);
            $table->decimal('transport_allowance', 14, 2);
            $table->decimal('weekly_hours', 5, 2);
            $table->unsignedTinyInteger('night_shift_start_hour');

            $table->decimal('factor_overtime_day', 5, 4);
            $table->decimal('factor_night_surcharge', 5, 4);
            $table->decimal('factor_sunday_holiday', 5, 4);
            $table->decimal('factor_overtime_night', 5, 4);
            $table->decimal('factor_overtime_day_sunday_holiday', 5, 4);
            $table->decimal('factor_sunday_holiday_night', 5, 4);
            $table->decimal('factor_overtime_night_sunday_holiday', 5, 4);

            $table->decimal('pct_health_employee', 8, 7);
            $table->decimal('pct_health_employer', 8, 7);
            $table->decimal('pct_pension_employee', 8, 7);
            $table->decimal('pct_pension_employer', 8, 7);
            $table->decimal('pct_caja_compensacion', 8, 7);
            $table->decimal('pct_icbf', 8, 7);
            $table->decimal('pct_sena', 8, 7);
            $table->decimal('pct_prima', 8, 7);
            $table->decimal('pct_cesantias', 8, 7);
            $table->decimal('pct_vacaciones', 8, 7);

            $table->json('arl_rates');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_legal_settings');
    }
};
