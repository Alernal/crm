<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public function index()
    {
        $cedula   = auth()->user()->documents()->where('type', 'cedula')->latest()->first();
        $tarjeta  = auth()->user()->documents()->where('type', 'tarjeta_profesional')->latest()->first();

        return view('documents.index', compact('cedula', 'tarjeta'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'type' => 'required|in:cedula,tarjeta_profesional',
            'file' => 'required|file|mimes:jpg,jpeg,png,webp,pdf|max:10240',
        ], [
            'file.mimes' => 'Solo se permiten imágenes (JPG, PNG, WEBP) o PDF.',
            'file.max'   => 'El archivo no puede superar 10 MB.',
        ]);

        $userId = auth()->id();
        $type   = $request->type;

        $existing = auth()->user()->documents()->where('type', $type)->first();
        if ($existing) {
            Storage::disk('local')->delete($existing->file_path);
            $existing->delete();
        }

        $file = $request->file('file');
        $path = $file->store("documents/{$userId}", 'local');

        auth()->user()->documents()->create([
            'type'              => $type,
            'original_filename' => $file->getClientOriginalName(),
            'file_path'         => $path,
            'file_type'         => $file->getMimeType(),
        ]);

        return back()->with('success', 'Documento subido correctamente.');
    }

    public function show(Document $document)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        return Storage::disk('local')->response(
            $document->file_path,
            $document->original_filename,
            ['Content-Type' => $document->file_type]
        );
    }

    public function destroy(Document $document)
    {
        abort_if($document->user_id !== auth()->id(), 403);

        Storage::disk('local')->delete($document->file_path);
        $document->delete();

        return back()->with('success', 'Documento eliminado.');
    }
}
