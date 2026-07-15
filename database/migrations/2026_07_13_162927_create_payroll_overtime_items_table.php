<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_overtime_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'extra_diurna',
                'recargo_nocturno',
                'dominical_festivo',
                'extra_nocturna',
                'extra_diurna_dominical_festivo',
                'dominical_festivo_nocturno',
                'extra_nocturna_dominical_festivo',
            ]);
            $table->decimal('hours', 6, 2)->default(0);
            $table->decimal('hourly_rate', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_overtime_items');
    }
};
