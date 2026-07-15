<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('first_name');
            $table->string('last_name');
            $table->enum('document_type', ['CC', 'CE', 'Pasaporte', 'PEP'])->default('CC');
            $table->string('document_number', 20);
            $table->string('email')->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('address')->nullable();
            $table->string('position')->nullable();
            $table->enum('contract_type', ['indefinido', 'fijo', 'obra_labor', 'aprendizaje'])->default('indefinido');
            $table->date('hire_date');
            $table->decimal('base_salary', 14, 2)->default(0);
            $table->enum('salary_type', ['fijo', 'variable'])->default('fijo');
            $table->decimal('weekly_hours_override', 5, 2)->nullable();
            $table->enum('transport_allowance_mode', ['automatico', 'siempre', 'nunca'])->default('automatico');
            $table->string('transport_allowance_note')->nullable();
            $table->string('eps')->nullable();
            $table->string('afp')->nullable();
            $table->enum('arl_risk_level', ['I', 'II', 'III', 'IV', 'V'])->default('I');
            $table->string('ccf')->nullable();
            $table->string('bank_name')->nullable();
            $table->string('account_type', 20)->nullable();
            $table->string('account_number', 30)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
