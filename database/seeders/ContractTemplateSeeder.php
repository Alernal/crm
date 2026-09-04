<?php

namespace Database\Seeders;

use App\Models\User;
use App\Services\DocumentEngine\DefaultTemplateProvisioner;
use Illuminate\Database\Seeder;

/**
 * A pesar del nombre (histórico), aprovisiona TODAS las plantillas oficiales
 * del sistema por usuario — contrato y propuesta — vía
 * DefaultTemplateProvisioner::provisionFor().
 */
class ContractTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $provisioner = app(DefaultTemplateProvisioner::class);

        User::all()->each(fn (User $user) => $provisioner->provisionFor($user));
    }
}
