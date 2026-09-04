<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `obligation_type` estaba restringido a un ENUM con los códigos "manuales"
 * antiguos (IVA, Renta, ICA...). Las obligaciones CALCULADAS automáticamente
 * (TaxObligationType::code, ej. IVA_BI, RTEFTE, RENTA_NAT) usan códigos
 * distintos que violan ese CHECK — por eso nunca fue posible persistir una
 * marca de "cumplida" para ellas (no había forma de insertar la fila). Se
 * relaja a string libre, como ya se hizo 3 veces antes en esta misma tabla
 * para otras columnas por la misma limitación de SQLite con ALTER de enums.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('tax_events', 'tax_events_backup');

        Schema::create('tax_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('obligation_type', 30);
            // 'calculado' es nuevo: marca de cumplimiento persistida para una ocurrencia
            // CALCULADA (TaxCalendarService, sin fila propia hasta que el usuario la marca
            // cumplida) — 'dian' ya está en uso real por la importación masiva del panel
            // admin (TaxCalendarImportController), no se puede reutilizar para esto.
            $table->enum('source', ['manual', 'dian', 'ica', 'calculado'])->default('manual');
            $table->date('due_date');
            $table->string('period', 100)->nullable();
            $table->unsignedTinyInteger('alert_days')->default(10);
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_pattern', ['monthly', 'bimonthly', 'quarterly', 'annual'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO tax_events (id, user_id, client_id, title, obligation_type, source,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at)
            SELECT id, user_id, client_id, title, obligation_type, source,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at
            FROM tax_events_backup
        ');

        Schema::drop('tax_events_backup');
    }

    public function down(): void
    {
        Schema::rename('tax_events', 'tax_events_backup');

        Schema::create('tax_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('obligation_type', [
                'IVA', 'IVA_anual',
                'Retefuente',
                'Renta',
                'ICA',
                'Patrimonio',
                'Activos_Exterior',
                'INC',
                'Exogena',
                'SIMPLE_anticipo', 'SIMPLE_anual',
                'Otro',
            ]);
            $table->enum('source', ['manual', 'dian', 'ica'])->default('manual');
            $table->date('due_date');
            $table->string('period', 100)->nullable();
            $table->unsignedTinyInteger('alert_days')->default(10);
            $table->enum('status', ['pending', 'completed', 'overdue'])->default('pending');
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurrence_pattern', ['monthly', 'bimonthly', 'quarterly', 'annual'])->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        DB::statement('
            INSERT INTO tax_events (id, user_id, client_id, title, obligation_type, source,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at)
            SELECT id, user_id, client_id, title, obligation_type, source,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at
            FROM tax_events_backup
            WHERE obligation_type IN (
                \'IVA\', \'IVA_anual\', \'Retefuente\', \'Renta\', \'ICA\', \'Patrimonio\',
                \'Activos_Exterior\', \'INC\', \'Exogena\', \'SIMPLE_anticipo\', \'SIMPLE_anual\', \'Otro\'
            )
        ');

        Schema::drop('tax_events_backup');
    }
};
