<?php

namespace Tests\Unit\DocumentEngine;

use App\Models\DocumentType;
use App\Models\User;
use App\Services\DocumentEngine\DocumentNumberingService;
use Database\Seeders\DocumentTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentNumberingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_numbers_increment_sequentially_per_type_and_year(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $user = User::factory()->create();
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $service = app(DocumentNumberingService::class);

        $first = $service->nextNumber($user, $type, 2026);
        $second = $service->nextNumber($user, $type, 2026);

        $this->assertSame('001-2026', $first['full_number']);
        $this->assertSame('002-2026', $second['full_number']);
    }

    public function test_numbering_resets_per_year(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $user = User::factory()->create();
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $service = app(DocumentNumberingService::class);

        $service->nextNumber($user, $type, 2026);
        $service->nextNumber($user, $type, 2026);
        $firstOf2027 = $service->nextNumber($user, $type, 2027);

        $this->assertSame('001-2027', $firstOf2027['full_number']);
    }

    public function test_numbering_is_independent_per_document_type(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $user = User::factory()->create();
        $contractType = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $proposalType = DocumentType::where('key', 'propuesta_comercial')->firstOrFail();
        $service = app(DocumentNumberingService::class);

        $service->nextNumber($user, $contractType, 2026);
        $firstProposal = $service->nextNumber($user, $proposalType, 2026);

        $this->assertSame('001-2026', $firstProposal['full_number']);
    }

    public function test_numbering_is_independent_per_user(): void
    {
        $this->seed(DocumentTypeSeeder::class);
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $type = DocumentType::where('key', 'contrato_servicios')->firstOrFail();
        $service = app(DocumentNumberingService::class);

        $service->nextNumber($userA, $type, 2026);
        $firstForB = $service->nextNumber($userB, $type, 2026);

        $this->assertSame('001-2026', $firstForB['full_number']);
    }
}
