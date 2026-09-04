<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class GeneratedDocument extends Model
{
    use SoftDeletes;

    const STATUSES = [
        'borrador'            => ['label' => 'Borrador',              'variant' => 'neutral'],
        'en_edicion'          => ['label' => 'En edición',            'variant' => 'info'],
        'pendiente_revision'  => ['label' => 'Pendiente de revisión', 'variant' => 'warning'],
        'revisado'            => ['label' => 'Revisado',              'variant' => 'info'],
        'aprobado'            => ['label' => 'Aprobado',              'variant' => 'success'],
        'firmado'             => ['label' => 'Firmado',               'variant' => 'success'],
        'finalizado'          => ['label' => 'Finalizado',            'variant' => 'success'],
        'anulado'             => ['label' => 'Anulado',               'variant' => 'neutral'],
    ];

    /**
     * Especialidades del Contrato de Prestación de Servicios: el título del documento
     * es siempre el mismo ("Contrato de Prestación de Servicios") sin importar la
     * especialidad — solo cambian el catálogo de servicios predeterminados que
     * precargan el constructor de Objeto del Contrato en el wizard (el usuario los
     * edita, elimina o agrega libremente después) y la frase introductoria +
     * disclaimer de esa misma cláusula. 'tributaria_financiera' no duplica texto:
     * reutiliza los mismos ítems de servicio de las otras dos.
     */
    public static function especialidades(): array
    {
        $tributaria = [
            ['nombre' => 'Asesoría integral en cumplimiento de obligaciones tributarias nacionales, departamentales y municipales', 'descripcion' => 'Incluye análisis y seguimiento del calendario tributario'],
            ['nombre' => 'Elaboración, revisión y acompañamiento en preparación de declaraciones tributarias periódicas', 'descripcion' => 'IVA, Retención en la Fuente, Renta, ICA, Información Exógena, SIIF y demás obligaciones tributarias'],
            ['nombre' => 'Planeación tributaria', 'descripcion' => 'Mediante estrategias lícitas que optimicen la carga fiscal conforme a la legislación colombiana'],
            ['nombre' => 'Análisis y diagnóstico de la situación tributaria de EL CLIENTE', 'descripcion' => null],
            ['nombre' => 'Acompañamiento frente a requerimientos, citaciones y fiscalizaciones', 'descripcion' => 'De la DIAN u otras autoridades tributarias'],
            ['nombre' => 'Emisión de conceptos técnicos e interpretaciones normativas en materia tributaria', 'descripcion' => null],
            ['nombre' => 'Asesoría en organización documental tributaria', 'descripcion' => 'Y parametrización de sistemas de información tributaria'],
        ];

        $financiera = [
            ['nombre' => 'Planeación financiera', 'descripcion' => 'Estratégica, orientada al cumplimiento de los objetivos económicos de EL CLIENTE'],
            ['nombre' => 'Elaboración, seguimiento y control de presupuestos', 'descripcion' => 'Presupuestos operativos y financieros'],
            ['nombre' => 'Gestión de tesorería', 'descripcion' => 'Incluyendo flujo de caja y capital de trabajo'],
            ['nombre' => 'Contabilidad financiera', 'descripcion' => 'Preparación y análisis de estados financieros bajo normas vigentes'],
            ['nombre' => 'Análisis financiero', 'descripcion' => 'Interpretación de indicadores de gestión, liquidez y rentabilidad'],
            ['nombre' => 'Evaluación de proyectos de inversión', 'descripcion' => 'Análisis de viabilidad y factibilidad financiera'],
        ];

        return [
            'tributaria' => [
                'label' => 'Consultoría Tributaria',
                'servicios' => $tributaria,
                // Usados por ServicesObjectClauseResolver para que la Cláusula de Objeto
                // (intro + disclaimer) hable de la especialidad correcta — el título del
                // documento en sí no cambia, solo esta cláusula y la lista de servicios.
                'objeto_texto' => 'consultoría tributaria',
                'disclaimer' => 'Las partes reconocen que la labor de EL CONSULTOR constituye obligación de medio y no de resultado. En ningún caso EL CONSULTOR garantiza resultados económicos o decisiones de la DIAN.',
            ],
            'financiera' => [
                'label' => 'Consultoría Financiera',
                'servicios' => $financiera,
                'objeto_texto' => 'consultoría financiera',
                'disclaimer' => 'Las partes reconocen que la labor de EL CONSULTOR constituye obligación de medio y no de resultado. En ningún caso EL CONSULTOR garantiza resultados económicos específicos derivados de sus recomendaciones financieras.',
            ],
            'tributaria_financiera' => [
                'label' => 'Consultoría Tributaria y Financiera',
                'servicios' => [...$tributaria, ...$financiera],
                'objeto_texto' => 'consultoría tributaria y financiera',
                'disclaimer' => 'Las partes reconocen que la labor de EL CONSULTOR constituye obligación de medio y no de resultado. En ningún caso EL CONSULTOR garantiza resultados económicos, decisiones de la DIAN, ni el resultado específico de sus recomendaciones financieras.',
            ],
        ];
    }

    protected $fillable = [
        'uuid', 'user_id', 'client_id', 'document_type_id', 'template_id', 'current_version_id',
        'number', 'year', 'full_number', 'status', 'responsible_user_id', 'is_favorite',
        'client_snapshot', 'contractor_snapshot', 'variables', 'signed_at',
    ];

    protected $casts = [
        'client_snapshot'      => 'array',
        'contractor_snapshot'  => 'array',
        'variables'            => 'array',
        'is_favorite'          => 'boolean',
        'signed_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $document) {
            $document->uuid ??= (string) Str::uuid();
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function documentType(): BelongsTo
    {
        return $this->belongsTo(DocumentType::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(DocumentVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(DocumentVersion::class, 'document_id')->orderByDesc('version_number');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(DocumentAuditLog::class, 'document_id')->orderByDesc('created_at');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(DocumentShare::class, 'document_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status]['label'] ?? $this->status;
    }

    public function statusVariant(): string
    {
        return self::STATUSES[$this->status]['variant'] ?? 'neutral';
    }
}
