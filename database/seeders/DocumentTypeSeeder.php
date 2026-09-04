<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            [
                'key' => 'contrato_servicios',
                'label' => 'Contrato de Prestación de Servicios',
                'icon' => 'file-signature',
                'default_prefix' => 'CT',
                'requires_dual_signature' => true,
            ],
            [
                'key' => 'propuesta_comercial',
                'label' => 'Propuesta de Servicios Profesionales',
                'icon' => 'file-text',
                'default_prefix' => 'PR',
                'requires_dual_signature' => false,
            ],
            [
                'key' => 'certificado_ingresos',
                'label' => 'Certificado de Ingresos',
                'icon' => 'user-check',
                'default_prefix' => 'CI',
                // Carta unilateral del contador — solo él firma, no el cliente.
                'requires_dual_signature' => false,
            ],
        ];

        foreach ($types as $type) {
            DocumentType::updateOrCreate(['key' => $type['key']], $type + ['is_active' => true]);
        }
    }
}
