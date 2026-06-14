<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('obligation_type', [
                'IVA',
                'Retefuente',
                'Renta',
                'ICA',
                'Patrimonio',
                'Activos_Exterior',
                'INC',
                'Otro',
            ]);
            $table->date('due_date');
            $table->string('period', 50)->nullable();
            $table->unsignedTinyInteger('alert_days')->default(10);
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_pattern', ['monthly', 'bimonthly', 'quarterly', 'annual'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_events');
    }
};
