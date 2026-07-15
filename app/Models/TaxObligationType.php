<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxObligationType extends Model
{
    protected $table = 'tax_obligation_types';

    /** Clave reservada de nit_key para obligaciones de fecha fija (igual para todos los NIT). */
    const FIXED_DATE_KEY = 'TODOS';

    protected $fillable = [
        'code', 'name', 'description', 'periodicity', 'nit_reference',
        'regime', 'responsibility_key', 'group_label', 'custom_periods',
        'alias_of', 'active', 'sort_order',
    ];

    protected $casts = [
        'active'         => 'boolean',
        'custom_periods' => 'array',
    ];

    public static array $periodicities = [
        'mensual'       => 'Mensual (12/año)',
        'bimestral'     => 'Bimestral (6/año)',
        'trimestral'    => 'Trimestral (4/año)',
        'cuatrimestral' => 'Cuatrimestral (3/año)',
        'anual'         => 'Anual (1/año)',
        'unica'         => 'Única',
    ];

    public static array $nitReferences = [
        'ultimo_digito'       => 'Último dígito (0-9)',
        'dos_ultimos_digitos' => 'Dos últimos dígitos (rangos)',
        'fecha_fija'          => 'Fecha única (sin importar NIT)',
    ];

    public static array $regimes = [
        'ordinario' => 'Régimen Ordinario',
        'simple'    => 'Régimen SIMPLE',
        'ambos'     => 'Ambos regímenes',
    ];

    public static function periodsFor(string $periodicity): array
    {
        return match ($periodicity) {
            'mensual'       => ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
            'bimestral'     => ['Ene-Feb','Mar-Abr','May-Jun','Jul-Ago','Sep-Oct','Nov-Dic'],
            'trimestral'    => ['Período 1','Período 2','Período 3','Período 4'],
            'cuatrimestral' => ['Ene-Abr','May-Ago','Sep-Dic'],
            'anual'         => ['Anual'],
            'unica'         => ['Única'],
            default         => ['Período 1'],
        };
    }

    /** Etiquetas de período reales de esta obligación: usa custom_periods si existe, si no las genéricas por periodicidad. */
    public function periodLabels(): array
    {
        return $this->custom_periods ?: self::periodsFor($this->periodicity);
    }

    public static array $colors = [
        'IVA_BI'      => '#3B82F6',
        'IVA_C4'      => '#1D4ED8',
        'IVA_EXT'     => '#2563EB',
        'RTEFTE'      => '#8B5CF6',
        'RENTA_GC'    => '#047857',
        'RENTA_NAT'   => '#10B981',
        'RENTA_JUR'   => '#059669',
        'EXOGENA'     => '#06B6D4',
        'INC'         => '#F97316',
        'SIMPLE_ANT'  => '#7C3AED',
        'SIMPLE_IVA'  => '#A78BFA',
        'SIMPLE_DEC'  => '#5B21B6',
        'ICA'         => '#F59E0B',
        'PATRIMONIO'  => '#EF4444',
        'CARBONO'     => '#65A30D',
        'GASOLINA'    => '#CA8A04',
        'PES_ANT'     => '#0EA5E9',
        'PES_DEC'     => '#0284C7',
        'PLASTICOS'   => '#14B8A6',
        'BEBIDAS'     => '#DB2777',
        'RUB'         => '#64748B',
        'PT_INFORME'  => '#9333EA',
        'PT_DOC'      => '#7E22CE',
        'PT_CBC'      => '#6B21A8',
        'ACTIVOS_EXT' => '#EC4899',
    ];

    public static function getColorMap(): array
    {
        return self::$colors;
    }

    public static function nitKeys(string $nitReference): array
    {
        return match ($nitReference) {
            'dos_ultimos_digitos' => self::twoDigitPairKeys(),
            'fecha_fija'          => [self::FIXED_DATE_KEY],
            default               => ['0','1','2','3','4','5','6','7','8','9'],
        };
    }

    /** Las 50 parejas reales que usa la DIAN para "dos últimos dígitos": 01-02, 03-04, ..., 97-98, 99-00. */
    public static function twoDigitPairKeys(): array
    {
        $keys = [];
        for ($i = 1; $i <= 97; $i += 2) {
            $keys[] = sprintf('%02d-%02d', $i, $i + 1);
        }
        $keys[] = '99-00';

        return $keys;
    }

    /** Dado el valor de los dos últimos dígitos del NIT (0-99), devuelve su pareja DIAN (ej: 47 → "47-48"). */
    public static function twoDigitPairKeyFor(int $lastTwo): string
    {
        if ($lastTwo <= 0) return '99-00';

        $start = $lastTwo % 2 === 0 ? $lastTwo - 1 : $lastTwo;

        return $start >= 99 ? '99-00' : sprintf('%02d-%02d', $start, $start + 1);
    }

    public function dueDates(): HasMany
    {
        return $this->hasMany(TaxDueDate::class, 'obligation_type_id');
    }

    public function aliasOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'alias_of');
    }
}
