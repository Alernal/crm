<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite can't alter enum constraints, so we recreate the table
        Schema::rename('tax_events', 'tax_events_backup');

        Schema::create('tax_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('obligation_type', [
                'IVA', 'Retefuente', 'Renta', 'ICA',
                'Patrimonio', 'Activos_Exterior', 'INC', 'Exogena', 'Otro',
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

        // Restore existing data
        DB::statement("
            INSERT INTO tax_events (id, user_id, client_id, title, obligation_type, source,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at)
            SELECT id, user_id, client_id, title, obligation_type, 'manual',
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at
            FROM tax_events_backup
        ");

        Schema::drop('tax_events_backup');
    }

    public function down(): void
    {
        Schema::rename('tax_events', 'tax_events_new');

        Schema::create('tax_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('obligation_type', [
                'IVA', 'Retefuente', 'Renta', 'ICA',
                'Patrimonio', 'Activos_Exterior', 'INC', 'Otro',
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

        DB::statement("
            INSERT INTO tax_events (id, user_id, client_id, title, obligation_type,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at)
            SELECT id, user_id, client_id, title, obligation_type,
                due_date, period, alert_days, status, is_recurring, recurrence_pattern, notes, created_at, updated_at
            FROM tax_events_new
            WHERE obligation_type != 'Exogena'
        ");

        Schema::drop('tax_events_new');
    }
};
