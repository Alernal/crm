<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('vacation_opening_balance_days', 6, 2)->default(0)->after('pension_exempt');
            $table->date('vacation_opening_balance_date')->nullable()->after('vacation_opening_balance_days');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['vacation_opening_balance_days', 'vacation_opening_balance_date']);
        });
    }
};
