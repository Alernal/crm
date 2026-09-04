<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapeo de drivers retirados hacia el nuevo catálogo reducido, para no
     * dejar renglones existentes con un driver que ya no es válido.
     */
    private const DRIVER_REMAP = [
        'ipc'                  => ['driver' => 'inflation'],
        'sales_growth'         => ['driver' => 'inflation'],
        'sales_growth_monthly' => ['driver' => 'inflation'],
        'payroll_growth'       => ['driver' => 'smmlv'],
        'rent_growth'          => ['driver' => 'custom_pct', 'rate' => 6.77],
        'utilities_growth'     => ['driver' => 'custom_pct', 'rate' => 8.00],
        'purchases_growth'     => ['driver' => 'custom_pct', 'rate' => 6.00],
        'interest_rate'        => ['driver' => 'custom_pct', 'rate' => 13.50],
        'services_growth'      => ['driver' => 'custom_pct', 'rate' => 7.00],
    ];

    public function up(): void
    {
        foreach (self::DRIVER_REMAP as $oldDriver => $mapping) {
            $update = ['projection_driver' => $mapping['driver']];
            if (isset($mapping['rate'])) {
                DB::table('budget_lines')
                    ->where('projection_driver', $oldDriver)
                    ->whereNull('custom_rate')
                    ->update(['custom_rate' => $mapping['rate']]);
            }
            DB::table('budget_lines')->where('projection_driver', $oldDriver)->update($update);
        }

        Schema::table('budget_lines', function (Blueprint $table) {
            $table->foreignId('service_id')->nullable()->after('section_id')
                ->constrained('services')->nullOnDelete();
            $table->string('legal_factor_key')->nullable()->after('projection_driver');
            $table->boolean('include_in_prestacional_base')->default(false)->after('legal_factor_key');
        });

        Schema::table('budget_lines', function (Blueprint $table) {
            $table->enum('projection_driver', [
                'manual', 'fixed', 'custom_pct', 'inflation', 'smmlv', 'legal_pct',
            ])->default('manual')->change();
        });
    }

    public function down(): void
    {
        Schema::table('budget_lines', function (Blueprint $table) {
            $table->enum('projection_driver', [
                'ipc', 'inflation', 'smmlv', 'sales_growth', 'sales_growth_monthly',
                'payroll_growth', 'rent_growth', 'utilities_growth', 'purchases_growth',
                'interest_rate', 'services_growth', 'fixed', 'manual', 'custom_pct',
            ])->default('ipc')->change();
        });

        Schema::table('budget_lines', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_id');
            $table->dropColumn(['legal_factor_key', 'include_in_prestacional_base']);
        });
    }
};
