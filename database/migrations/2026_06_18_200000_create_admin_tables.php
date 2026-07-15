<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_modules', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('icon')->default('cube');
            $table->boolean('active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();
        });

        Schema::create('admin_custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('module_key');
            $table->string('name');
            $table->string('label');
            $table->enum('type', ['text','number','email','date','select','checkbox','textarea','phone','nit'])->default('text');
            $table->boolean('required')->default(false);
            $table->boolean('visible')->default(true);
            $table->boolean('active')->default(true);
            $table->unsignedInteger('order')->default(0);
            $table->string('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->timestamps();
        });

        Schema::create('admin_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('admin_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('admin_roles')->cascadeOnDelete();
            $table->string('module_key');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_create')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->boolean('can_delete')->default(false);
            $table->timestamps();
            $table->unique(['role_id', 'module_key']);
        });

        Schema::create('admin_logs', function (Blueprint $table) {
            $table->id();
            $table->string('admin_user')->default('superadmin');
            $table->string('action');
            $table->string('module')->nullable();
            $table->string('record_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('global_parameters', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['string','number','boolean','json','color'])->default('string');
            $table->string('label');
            $table->string('description')->nullable();
            $table->string('group')->default('general');
            $table->boolean('editable')->default(true);
            $table->timestamps();
        });

        Schema::create('tax_calendar_configs', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->string('tax_type');
            $table->string('period');
            $table->string('name');
            $table->date('due_date');
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        $this->seedDefaults();
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_calendar_configs');
        Schema::dropIfExists('global_parameters');
        Schema::dropIfExists('admin_logs');
        Schema::dropIfExists('admin_permissions');
        Schema::dropIfExists('admin_roles');
        Schema::dropIfExists('admin_custom_fields');
        Schema::dropIfExists('admin_modules');
    }

    private function seedDefaults(): void
    {
        $modules = [
            ['key' => 'clients',      'name' => 'Clientes',            'description' => 'Gestión de clientes y datos tributarios', 'icon' => 'users',          'order' => 1],
            ['key' => 'services',     'name' => 'Servicios',           'description' => 'Catálogo de servicios del contador',       'icon' => 'briefcase',      'order' => 2],
            ['key' => 'invoices',     'name' => 'Cuentas de Cobro',    'description' => 'Facturación y documentos de cobro',        'icon' => 'document-text', 'order' => 3],
            ['key' => 'cartera',      'name' => 'Cartera',             'description' => 'Seguimiento de pagos y mora',              'icon' => 'credit-card',    'order' => 4],
            ['key' => 'tax-calendar', 'name' => 'Calendario Tributario','description' => 'Obligaciones fiscales por cliente',       'icon' => 'calendar',       'order' => 5],
            ['key' => 'documents',    'name' => 'Documentos',          'description' => 'Documentos con marca de agua',             'icon' => 'folder',         'order' => 6],
        ];

        foreach ($modules as $m) {
            \DB::table('admin_modules')->insert(array_merge($m, [
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $params = [
            ['key' => 'company_name',        'label' => 'Nombre de la empresa',        'value' => 'ALERNAL S.A.S.',               'type' => 'string',  'group' => 'empresa',    'description' => 'Razón social del pie de página en PDFs'],
            ['key' => 'company_nit',         'label' => 'NIT empresa',                 'value' => '',                             'type' => 'string',  'group' => 'empresa',    'description' => 'NIT del despacho contable'],
            ['key' => 'invoice_footer',      'label' => 'Pie de página cuentas cobro', 'value' => 'Elaborado por ALERNAL S.A.S. — Construyendo el mañana', 'type' => 'string', 'group' => 'empresa', 'description' => 'Texto fijo al pie del PDF de cuentas de cobro'],
            ['key' => 'alert_days_before',   'label' => 'Días de alerta anticipada',   'value' => '30',                           'type' => 'number',  'group' => 'alertas',    'description' => 'Días de anticipación para alertas tributarias'],
            ['key' => 'max_pdf_per_minute',  'label' => 'Máx. PDFs por minuto',        'value' => '10',                           'type' => 'number',  'group' => 'limites',    'description' => 'Throttle de generación de PDFs por usuario'],
            ['key' => 'iva_rate',            'label' => 'Tasa IVA (%)',                'value' => '19',                           'type' => 'number',  'group' => 'tributario', 'description' => 'Porcentaje de IVA aplicado en servicios'],
            ['key' => 'primary_color',       'label' => 'Color primario UI',           'value' => '#1e3a5f',                      'type' => 'color',   'group' => 'apariencia', 'description' => 'Color principal del sidebar y botones'],
            ['key' => 'watermark_opacity',   'label' => 'Opacidad marca de agua',      'value' => '0.15',                         'type' => 'number',  'group' => 'documentos', 'description' => 'Opacidad por defecto de la marca de agua en PDFs'],
        ];

        foreach ($params as $p) {
            \DB::table('global_parameters')->insert(array_merge($p, [
                'editable'   => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }

        $roles = [
            ['name' => 'Administrador', 'slug' => 'admin',       'description' => 'Acceso completo a todos los módulos'],
            ['name' => 'Contador',      'slug' => 'contador',    'description' => 'Gestión de clientes, cobros y cartera'],
            ['name' => 'Auxiliar',      'slug' => 'auxiliar',    'description' => 'Solo lectura y creación de clientes'],
        ];

        foreach ($roles as $r) {
            \DB::table('admin_roles')->insert(array_merge($r, [
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]));
        }
    }
};
