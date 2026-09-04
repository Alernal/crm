<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('budget_sections', function (Blueprint $table) {
            $table->boolean('is_outflow')->default(false)->after('sort_order');
        });

        Schema::table('budget_values', function (Blueprint $table) {
            $table->enum('value_type', ['budgeted', 'actual'])->default('budgeted')->after('line_id');
        });

        Schema::table('budget_values', function (Blueprint $table) {
            $table->dropUnique(['line_id', 'period_index']);
            $table->unique(['line_id', 'period_index', 'value_type']);
        });
    }

    public function down(): void
    {
        Schema::table('budget_values', function (Blueprint $table) {
            $table->dropUnique(['line_id', 'period_index', 'value_type']);
            $table->unique(['line_id', 'period_index']);
            $table->dropColumn('value_type');
        });

        Schema::table('budget_sections', function (Blueprint $table) {
            $table->dropColumn('is_outflow');
        });
    }
};
