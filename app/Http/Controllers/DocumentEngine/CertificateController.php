<?php

namespace App\Http\Controllers\DocumentEngine;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentEngine\Concerns\ResolvesLogoDataUri;
use App\Mail\CertificateDocumentMail;
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

/**
 * Submódulo "Certificados". Certificado de Ingresos (persona natural) es
 * el único tipo con motor de generación implementado hoy — Certificado
 * de Accionistas queda pendiente (ver roadmap en CLAUDE.md), el índice
 * ya deja espacio para él.
 */
class CertificateController extends Controller
{
    use AuthorizesRequests, ResolvesLogoDataUri;

    public function index(Request $request)
    {
        $type = DocumentType::where('key', 'certificado_ingresos')->first();

        $query = GeneratedDocument::where('user_id', Auth::id())
            ->when($type, fn ($q) => $q->where('document_type_id', $type->id))
            ->with(['client', 'documentType'])
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('full_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        $documents = $query->paginate(12)->withQueryString();

        $clients = Client::where('user_id', Auth::id())->where('person_type', 'natural')->orderBy('name')->get(['id', 'name']);

        return view('documents.certificates.index', compact('documents', 'clients'));
    }

    public function create()
    {
        // Solo personas naturales — un cliente jurídica nunca debe aparecer aquí.
        $clients = Client::where('user_id', Auth::id())
            ->where('status', 'active')
            ->where('person_type', 'natural')
            ->orderBy('name')
            ->get();
        $template = $this->defaultCertificateTemplate();
        $grupoNiifOptions = [
            'no_aplica' => 'No aplica',
            '1' => 'Grupo 1',
            '2' => 'Grupo 2',
            '3' => 'Grupo 3',
        ];

        return view('documents.certificates.wizard', compact('clients', 'template', 'grupoNiifOptions'));
    }

    public function store(Request $request, DocumentGenerationService $generationService)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'destinatario' => 'required|string|max:255',
            'ciudad_destinatario' => 'nullable|string|max:100',
            'actividad_economica' => 'required|string|max:255',
            'periodo_inicio' => 'required|date',
            'periodo_fin' => 'required|date|after_or_equal:periodo_inicio',
            'procedimientos' => 'required|array|min:1',
            'procedimientos.*' => 'required|string|max:255',
            'resultado_revision' => 'required|string|max:3000',
            'ingreso_valor' => 'required|numeric|min:0',
            'ingreso_periodicidad' => 'required|in:anual,mensual,otro',
            'grupo_niif' => 'required|in:no_aplica,1,2,3',
            'ciudad_expedicion' => 'required|string|max:100',
            'fecha_expedicion' => 'required|date',
        ]);

        $client = Client::where('user_id', Auth::id())->findOrFail($data['client_id']);

        // Defensa en profundidad: el selector del wizard ya filtra por persona natural,
        // pero un client_id manipulado a mano no debe poder colarse.
        abort_if($client->person_type !== 'natural', 422, 'Los certificados de ingresos solo se emiten para clientes persona natural.');

        $template = $this->defaultCertificateTemplate();

        abort_if($template === null, 422, 'No hay una plantilla activa para Certificados de Ingresos.');

        $variables = [
            'destinatario' => $data['destinatario'],
            // '' y no null: el passthrough de CertificatePlaceholderProvider deja el
            // placeholder literal visible si el valor es null (nunca inventa datos), pero
            // para un campo opcional realmente vacío el resultado esperado es "nada", no
            // el token {{...}} sin resolver — mismo criterio que el resto del motor.
            'ciudad_destinatario' => $data['ciudad_destinatario'] ?? '',
            // Línea aparte SOLO si hay ciudad — evita que el encabezado deje una línea
            // en blanco huérfana (y un doble salto de línea antes de "Asunto:") cuando
            // el campo opcional "Ciudad del destinatario" se deja vacío. [[BR]] es el
            // marcador seguro de ClauseEngine::wrapClauseHtml() (ver certificado_encabezado).
            'destinatario_ciudad_linea' => ! empty($data['ciudad_destinatario']) ? '[[BR]]'.$data['ciudad_destinatario'] : '',
            'actividad_economica' => $data['actividad_economica'],
            'periodo' => [
                'fecha_inicio' => $data['periodo_inicio'],
                'fecha_fin' => $data['periodo_fin'],
            ],
            'procedimientos' => $data['procedimientos'],
            'resultado_revision' => $data['resultado_revision'],
            'ingreso' => [
                'valor' => $data['ingreso_valor'],
                'periodicidad' => $data['ingreso_periodicidad'],
            ],
            'grupo_niif' => $data['grupo_niif'],
            'ciudad_expedicion' => $data['ciudad_expedicion'],
            'fecha_expedicion' => $this->formatLegalDate(Carbon::parse($data['fecha_expedicion'])),
            'fecha_expedicion_corta' => Carbon::parse($data['fecha_expedicion'])->locale('es')->isoFormat('D [de] MMMM [de] YYYY'),
        ];

        $document = $generationService->generate(Auth::user(), $client, $template, $variables);

        return redirect()->route('documents.certificates.show', $document)
            ->with('success', "Certificado {$document->full_number} generado correctamente.");
    }

    public function show(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        return view('documents.certificates.show', compact('document'));
    }

    public function pdf(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        $document->auditLogs()->create(['user_id' => Auth::id(), 'event' => 'downloaded_pdf']);

        $logoDataUri = $this->logoDataUriFor($document->user);
        $pdf = Pdf::loadView('documents.certificates.pdf', compact('document', 'logoDataUri'))->setPaper('letter');

        return $pdf->download("{$document->full_number}.pdf");
    }

    public function printView(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        $document->auditLogs()->create(['user_id' => Auth::id(), 'event' => 'printed']);

        $logoDataUri = $this->logoDataUriFor($document->user);

        return view('documents.certificates.pdf', compact('document', 'logoDataUri'));
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
            new CertificateDocumentMail($document, Auth::user(), $data['message'] ?? null)
        );

        $document->auditLogs()->create([
            'user_id' => Auth::id(),
            'event' => 'emailed',
            'meta' => ['to' => $data['email']],
        ]);

        return back()->with('success', "{$document->documentType->label} {$document->full_number} enviado a {$data['email']}.");
    }

    public function destroy(GeneratedDocument $document)
    {
        $this->authorize('delete', $document);

        $document->delete();

        return redirect()->route('documents.certificates.index')->with('success', 'Documento eliminado.');
    }

    private function defaultCertificateTemplate(): ?DocumentTemplate
    {
        $type = DocumentType::where('key', 'certificado_ingresos')->first();
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
