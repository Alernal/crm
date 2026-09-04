<?php

namespace App\Http\Controllers\DocumentEngine;

use App\Http\Controllers\Controller;
use App\Http\Controllers\DocumentEngine\Concerns\ResolvesLogoDataUri;
use App\Mail\ContractDocumentMail;
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

class GeneratedDocumentController extends Controller
{
    use AuthorizesRequests, ResolvesLogoDataUri;

    public function index(Request $request)
    {
        $typeKeys = DocumentType::whereIn('key', ['contrato_servicios', 'propuesta_comercial'])->pluck('id');

        $query = GeneratedDocument::where('user_id', Auth::id())
            ->whereIn('document_type_id', $typeKeys)
            ->with(['client', 'documentType'])
            ->orderByDesc('created_at');

        if ($request->filled('q')) {
            $search = $request->string('q');
            $query->where(function ($q) use ($search) {
                $q->where('full_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($c) => $c->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->integer('client_id'));
        }

        if ($request->filled('year')) {
            $query->where('year', $request->integer('year'));
        }

        $documents = $query->paginate(12)->withQueryString();

        $clients = Client::where('user_id', Auth::id())->orderBy('name')->get(['id', 'name']);
        $years = GeneratedDocument::where('user_id', Auth::id())
            ->whereIn('document_type_id', $typeKeys)
            ->distinct()->orderByDesc('year')->pluck('year');

        return view('documents.contracts.index', compact('documents', 'clients', 'years'));
    }

    public function create()
    {
        $clients = Client::where('user_id', Auth::id())->where('status', 'active')->orderBy('name')->get();
        $services = Auth::user()->services()->where('status', 'active')->orderBy('name')->get(['id', 'name', 'base_price']);
        $template = $this->defaultContractTemplate();
        $especialidades = GeneratedDocument::especialidades();

        return view('documents.contracts.wizard', compact('clients', 'services', 'template', 'especialidades'));
    }

    public function store(Request $request, DocumentGenerationService $generationService)
    {
        $data = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'especialidad' => 'required|in:tributaria,financiera,tributaria_financiera',
            'ciudad_celebracion' => 'required|string|max:100',
            'fecha_elaboracion' => 'required|date',
            'servicios' => 'required|array|min:1',
            'servicios.*.nombre' => 'required|string|max:255',
            'servicios.*.descripcion' => 'nullable|string|max:500',
            'duracion_modo' => 'required|in:meses,fechas',
            'duracion_meses' => 'required_if:duracion_modo,meses|nullable|integer|min:1|max:120',
            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'required_if:duracion_modo,fechas|nullable|date|after:fecha_inicio',
            'valor' => 'required|numeric|min:0',
            'periodicidad' => 'required|in:unico,mensual,bimestral,trimestral,semestral,anual',
            'valor_periodico' => 'nullable|numeric|min:0',
        ]);

        $client = Client::where('user_id', Auth::id())->findOrFail($data['client_id']);
        $template = $this->defaultContractTemplate();

        abort_if($template === null, 422, 'No hay una plantilla activa para Contratos de Prestación de Servicios.');

        $variables = [
            'especialidad' => $data['especialidad'],
            // El título del contrato es siempre el mismo sin importar la especialidad
            // elegida — solo cambian los servicios y la Cláusula de Objeto (ver
            // ServicesObjectClauseResolver).
            'titulo_documento' => mb_strtoupper($template->documentType->label),
            'ciudad_celebracion' => $data['ciudad_celebracion'],
            'fecha_elaboracion' => $this->formatLegalDate(Carbon::parse($data['fecha_elaboracion'])),
            'servicios' => $data['servicios'],
            'duracion' => [
                'modo' => $data['duracion_modo'],
                'meses' => $data['duracion_meses'] ?? null,
                'fecha_inicio' => $data['fecha_inicio'],
                'fecha_fin' => $data['fecha_fin'] ?? null,
            ],
            'honorarios' => [
                'valor' => $data['valor'],
                'periodicidad' => $data['periodicidad'],
                'valor_periodico' => $data['valor_periodico'] ?? $data['valor'],
            ],
        ];

        $document = $generationService->generate(Auth::user(), $client, $template, $variables);

        return redirect()->route('documents.contracts.show', $document)
            ->with('success', "Contrato {$document->full_number} generado correctamente.");
    }

    public function show(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        return view('documents.contracts.show', compact('document'));
    }

    public function pdf(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        $document->auditLogs()->create(['user_id' => Auth::id(), 'event' => 'downloaded_pdf']);

        $logoDataUri = $this->logoDataUriFor($document->user);
        $pdf = Pdf::loadView('documents.contracts.pdf', compact('document', 'logoDataUri'))->setPaper('letter');

        return $pdf->download("{$document->full_number}.pdf");
    }

    public function printView(GeneratedDocument $document)
    {
        $this->authorize('view', $document);

        $document->load(['currentVersion', 'client', 'documentType', 'user']);

        $document->auditLogs()->create(['user_id' => Auth::id(), 'event' => 'printed']);

        $logoDataUri = $this->logoDataUriFor($document->user);

        return view('documents.contracts.pdf', compact('document', 'logoDataUri'));
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
            new ContractDocumentMail($document, Auth::user(), $data['message'] ?? null)
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

        return redirect()->route('documents.contracts.index')->with('success', 'Documento eliminado.');
    }

    private function defaultContractTemplate(): ?DocumentTemplate
    {
        $type = DocumentType::where('key', 'contrato_servicios')->first();
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
