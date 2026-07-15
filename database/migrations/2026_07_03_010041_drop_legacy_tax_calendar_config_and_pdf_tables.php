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
        // Reemplazadas por TaxObligationType + TaxDueDate (fechas por dígito de NIT)
        // y por el nuevo importador de Excel/CSV del calendario DIAN.
        Schema::dropIfExists('tax_calendar_configs');
        Schema::dropIfExists('tax_calendar_pdfs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('tax_calendar_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('tax_type');
            $table->string('period');
            $table->string('name');
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_calendar_pdfs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('original_name', 255);
            $table->string('path', 512);
            $table->enum('status', ['pending', 'reviewed', 'imported', 'failed'])->default('pending');
            $table->json('extracted_data')->nullable();
            $table->text('parse_notes')->nullable();
            $table->timestamp('imported_at')->nullable();
            $table->timestamps();
        });
    }
};
