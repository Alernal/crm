<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('document_type', ['NIT', 'CC', 'CE', 'Pasaporte'])->default('NIT');
            $table->string('document_number', 20);
            $table->string('dv', 1)->nullable();
            $table->enum('person_type', ['natural', 'juridica'])->default('juridica');
            $table->enum('tax_regime', ['gran_contribuyente', 'autorretenedor', 'agente_retencion_iva', 'regimen_simple', 'no_aplica'])->default('no_aplica');
            $table->json('tax_responsibilities')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('department', 100)->nullable();
            $table->string('contact_person')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
