<?php

namespace App\Http\Controllers\DocumentEngine;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentEngine\Concerns\ResolvesLogoDataUri;
use App\Mail\ProposalDocumentMail;
use App\Models\Client;
use App\Models\DocumentTemplate;
use App\Models\DocumentType;
use App\Models\GeneratedDocument;
use App\Services\DocumentEngine\DocumentGenerationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class GeneratedProposalController extends Controller
{
    use AuthorizesRequests, ResolvesLogoDataUri;

    public function create()
    {
        $clients = Client::where('user_id', Auth::id())->where('status', 'active')->orderBy('name')->get();
        $services = Auth::user()->services()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'base_price']);
        $template = $this->defaultProposalTemplate();
        $especialidades = GeneratedDocument::especialidades();

        return view('documents.proposals.wizard', compact('clients', 'services', 'template', 'especialidades'));
    }

    public function store(Request $request, DocumentGenerationService $generationService)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'especialidad' => 'required|in:tributaria,financiera,tributaria_financiera',
            'servicios' => 'required|array|min:1',
            'servicios.*.nombre' => 'required|string|max:255',
            'servicios.*.descripcion' => 'nullable|string|max:500',
            'descripcion_proyecto' => 'required|string|max:2000',
            'objetivo_general' => 'required|string|max:1000',
            'objetivos_especificos' => 'required|array|min:1|max:4',
            'objetivos_especificos.*' => 'required|string|max:500',
            'metodologia_fase1' => 'required|string|max:1000',
            'metodologia_fase2' => 'required|string|max:1000',
            'metodologia_fase3' => 'required|string|max:1000',
            'ciudad_celebracion' => 'required|string|max:100',
            'fecha_elaboracion' => 'required|date',
            'validez_dias' => 'required|integer|min:1|max:90',
            'valor' => 'required|numeric|min:0',
            'forma_pago' => 'required|in:unico,cuotas',
            'cuotas' => 'required_if:forma_pago,cuotas|array',
            // required_if (no required_with): el campo :disabled deshabilita los inputs de
            // cuota en el wizard cuando forma_pago no es 'cuotas', pero por si acaso llega
            // una fila vacía igual (ej. petición manual), la validación se ata al valor real
            // de forma_pago en vez de a la sola presencia del array — evita el 422 espurio
            // "cuotas.0.vencimiento field is required" al enviar la propuesta en pago único.
            'cuotas.*.valor' => 'nullable|required_if:forma_pago,cuotas|numeric|min:0',
            'cuotas.*.vencimiento' => 'nullable|required_if:forma_pago,cuotas|date',
            'condiciones_pago' => 'nullable|string|max:1000',
        ]);

        $client = Client::where('user_id', Auth::id())->findOrFail($data['client_id']);
        $template = $this->defaultProposalTemplate();

        abort_if($template === null, 422, 'No hay una plantilla activa para Propuestas de Servicios Profesionales.');

        $variables = [
            'especialidad' => $data['especialidad'],
            'servicios' => $data['servicios'],
            'descripcion_proyecto' => $data['descripcion_proyecto'],
            'objetivos' => [
                'general' => $data['objetivo_general'],
                'especificos' => $data['objetivos_especificos'],
            ],
            'metodologia_fase1' => $data['metodologia_fase1'],
            'metodologia_fase2' => $data['metodologia_fase2'],
            'metodologia_fase3' => $data['metodologia_fase3'],
            'ciudad_celebracion' => $data['ciudad_celebracion'],
            // 'fecha_elaboracion' se usa en la cláusula de firmas ("a los {{...}}", estilo
            // legal); 'fecha_elaboracion_corta' es la misma fecha en formato simple para
            // la sección de Datos Generales — evita "Fecha de elaboración: 15 días del
            // mes de..." que suena a fecha de firma, no a un campo de encabezado.
            'fecha_elaboracion' => $this->formatLegalDate(Carbon::parse($data['fecha_elaboracion'])),
            'fecha_elaboracion_corta' => Carbon::parse($data['fecha_elaboracion'])->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
            'validez_dias' => $data['validez_dias'],
            'validez' => [
                'fecha_elaboracion_iso' => $data['fecha_elaboracion'],
                'dias' => $data['validez_dias'],
            ],
            'inversion' => [
                'valor' => $data['valor'],
                'forma_pago' => $data['forma_pago'],
                'cuotas' => $data['cuotas'] ?? [],
                'condiciones_pago' => $data['condiciones_pago'] ?? null,
            ],
        ];

        $document = $generationService->generate(Auth::user(), $client, $template, $variables);

        return redirect()->route('documents.proposals.show', $document)
            ->with('success', "Propuesta {$document->full_number} generada correctamente.");
    }

    public function show(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        return view('documents.proposals.show', compact('document'));
    }

    public function pdf(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        $document->auditLogs()->create(['user_id' => Auth::id(), 'event' => 'downloaded_pdf']);

        $logoDataUri = $this->logoDataUriFor($document->user);
        $pdf = Pdf::loadView('documents.proposals.pdf', compact('document', 'logoDataUri'))->setPaper('letter');

        return $pdf->download("{$document->full_number}.pdf");
    }

    public function printView(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        $document->auditLogs()->create(['user_id' => Auth::id(), 'event' => 'printed']);

        $logoDataUri = $this->logoDataUriFor($document->user);

        return view('documents.proposals.pdf', compact('document', 'logoDataUri'));
    }

    public function sendEmail(Request $request, GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $data = $request->validate([
            'email' => 'required|email|max:255',
            'message' => 'nullable|string|max:1000',
        ]);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        Mail::to($data['email'])->send(
            new ProposalDocumentMail($document, Auth::user(), $data['message'] ?? null)
        );

        $document->auditLogs()->create([
            'user_id' => Auth::id(),
            'event' => 'emailed',
            'meta' => ['to' => $data['email']],
        ]);

        return back()->with('success', "{$document->documentType->label} {$document->full_number} enviada a {$data['email']}.");
    }

    public function destroy(GeneratedDocument $document)
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.contracts.index')->with('success', 'Documento eliminado.');
    }

    private function defaultProposalTemplate(): ?DocumentTemplate
    {
        $type = DocumentType::where('key', 'propuesta_comercial')->first();
        if (! $type) {
            return null;
        }

        return DocumentTemplate::where('user_id', Auth::id())
            ->where('document_type_id', $type->id)
            ->where('status', DocumentTemplate::STATUS_ACTIVE)
            ->orderByDesc('is_system_default')
            ->first();
    }

    private function formatLegalDate(Carbon $date): string
    {
        return $date->locale('es')->isoFormat('D [días del mes de] MMMM [de] YYYY');
    }
}
