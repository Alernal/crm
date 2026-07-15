<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prima_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prima_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('worked_days', 6, 2)->default(0);
            $table->decimal('prima_value', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['prima_settlement_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prima_settlement_items');
    }
};
