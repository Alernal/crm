<?php

namespace App\Services\DocumentEngine;

use App\Models\DocumentType;
use App\Models\DocumentTypeCounter;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Numeración atómica por usuario + tipo de documento + año — mismo patrón
 * lockForUpdate()+increment() que InvoiceController usa hoy sobre
 * clients.invoice_consecutive, aplicado a document_type_counters.
 */
final class DocumentNumberingService
{
    /** Retorna ['number' => '015', 'year' => 2026, 'full_number' => '015-2026']. */
    public function nextNumber(User $user, DocumentType $documentType, ?int $year = null): array
    {
        $year ??= (int) now()->year;

        return DB::transaction(function () use ($user, $documentType, $year) {
            $counter = DocumentTypeCounter::where('user_id', $user->id)
                ->where('document_type_id', $documentType->id)
                ->where('year', $year)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = DocumentTypeCounter::create([
                    'user_id' => $user->id,
                    'document_type_id' => $documentType->id,
                    'year' => $year,
                    'prefix' => $documentType->default_prefix,
                    'consecutive' => 0,
                ]);
            }

            $counter->increment('consecutive');

            $number = str_pad((string) $counter->consecutive, 3, '0', STR_PAD_LEFT);

            return [
                'number' => $number,
                'year' => $year,
                'full_number' => "{$number}-{$year}",
            ];
        });
    }
}
