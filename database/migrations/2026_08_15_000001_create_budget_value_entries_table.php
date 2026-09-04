<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('budget_value_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('budget_line_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('period_index');
            $table->date('entry_date');
            $table->string('tercero')->nullable();
            $table->string('description')->nullable();
            $table->decimal('value', 15, 2);
            $table->timestamps();

            $table->index(['budget_line_id', 'period_index']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('budget_value_entries');
    }
};
