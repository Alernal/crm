<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // La reconstrucción anterior de tax_obligation_types dejó la FK de tax_due_dates
        // apuntando a la tabla temporal "tax_obligation_types_backup" (ya eliminada).
        // La tabla está vacía en este punto, así que se recrea limpia.
        Schema::dropIfExists('tax_due_dates');

        Schema::create('tax_due_dates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('obligation_type_id')->constrained('tax_obligation_types')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('period_number');
            $table->string('period_label', 64);
            $table->string('nit_key', 10);
            $table->date('due_date');
            $table->timestamps();
            $table->unique(['obligation_type_id', 'year', 'period_number', 'nit_key'], 'unique_tax_due_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tax_due_dates');
    }
};
