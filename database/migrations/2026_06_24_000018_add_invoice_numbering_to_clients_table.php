<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->string('invoice_prefix', 20)->nullable()->after('notes');
            $table->unsignedInteger('invoice_consecutive')->default(0)->after('invoice_prefix');
        });
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn(['invoice_prefix', 'invoice_consecutive']);
        });
    }
};
