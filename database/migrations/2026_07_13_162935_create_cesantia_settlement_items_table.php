<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cesantia_settlement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cesantia_settlement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->decimal('worked_days', 6, 2)->default(0);
            $table->decimal('cesantias_value', 14, 2)->default(0);
            $table->decimal('interest_value', 14, 2)->default(0);
            $table->timestamps();

            $table->unique(['cesantia_settlement_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cesantia_settlement_items');
    }
};
