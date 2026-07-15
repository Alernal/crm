<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 2)->default(0)->after('vat_rate');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('discount_amount', 12, 2)->default(0)->after('vat_amount');
            $table->decimal('withholding_rate', 5, 2)->default(0)->after('discount_amount');
            $table->decimal('withholding_amount', 12, 2)->default(0)->after('withholding_rate');
        });
    }

    public function down(): void
    {
        Schema::table('invoice_items', function (Blueprint $table) {
            $table->dropColumn('discount_rate');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'withholding_rate', 'withholding_amount']);
        });
    }
};
