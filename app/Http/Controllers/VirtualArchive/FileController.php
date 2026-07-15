<?php

namespace App\Http\Controllers\VirtualArchive;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VirtualFile;
use App\Models\VirtualFolder;
use App\Services\VirtualArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FileController extends Controller
{
    public function __construct(private VirtualArchiveService $archive) {}

    public function index(Request $request, Client $client): View
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $folderId = $request->query('folder');
        $folder   = null;

        if ($folderId) {
            $folder = VirtualFolder::where('id', $folderId)
                ->where('client_id', $client->id)
                ->firstOrFail();
        }

        $tree = $this->archive->getFolderTree($client);

        $filesQuery = VirtualFile::where('client_id', $client->id);
        if ($folderId) {
            $filesQuery->where('folder_id', $folderId);
        } else {
            $filesQuery->whereNull('folder_id');
        }

        $files = $filesQuery->orderBy('original_filename')->get();

        $subfolders = VirtualFolder::where('client_id', $client->id)
            ->where('parent_id', $folder?->id)
            ->orderBy('name')
            ->get();

        $breadcrumbs = $folder ? $folder->breadcrumbs() : [];

        return view('virtual-archive.index', compact(
            'client', 'folder', 'tree', 'files', 'subfolders', 'breadcrumbs'
        ));
    }

    public function store(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $maxMb = VirtualArchiveService::MAX_FILE_SIZE_MB;
        $request->validate([
            'file'      => "required|file|max:{$maxMb}|mimetypes:" . implode(',', VirtualArchiveService::ALLOWED_MIME_TYPES),
            'folder_id' => 'nullable|integer|exists:virtual_folders,id',
        ], [
            'file.max'       => "El archivo no puede superar {$maxMb} MB.",
            'file.mimetypes' => 'Tipo de archivo no permitido.',
        ]);

        $this->archive->uploadFile(
            $client,
            $request->file('file'),
            $request->user()->id,
            $request->folder_id
        );

        $redirect = route('archive.files.index', $client);
        if ($request->folder_id) $redirect .= '?folder=' . $request->folder_id;

        return redirect($redirect)->with('success', 'Archivo subido correctamente.');
    }

    public function download(Request $request, Client $client, VirtualFile $file): Response
    {
        abort_if($client->user_id !== $request->user()->id, 403);
        abort_if($file->client_id !== $client->id, 403);

        \App\Models\VirtualFileLog::create([
            'user_id'         => $request->user()->id,
            'virtual_file_id' => $file->id,
            'filename'        => $file->original_filename,
            'action'          => 'download',
            'created_at'      => now(),
        ]);

        return $this->archive->getStorageResponse($file);
    }

    public function update(Request $request, Client $client, VirtualFile $file): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);
        abort_if($file->client_id !== $client->id, 403);

        $request->validate(['original_filename' => 'required|string|max:200']);

        $this->archive->renameFile($file, $request->original_filename, $request->user()->id);

        return back()->with('success', 'Archivo renombrado.');
    }

    public function move(Request $request, Client $client, VirtualFile $file): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);
        abort_if($file->client_id !== $client->id, 403);

        $request->validate(['folder_id' => 'nullable|integer|exists:virtual_folders,id']);

        $this->archive->moveFile($file, $request->folder_id, $request->user()->id);

        return back()->with('success', 'Archivo movido.');
    }

    public function destroy(Request $request, Client $client, VirtualFile $file): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);
        abort_if($file->client_id !== $client->id, 403);

        $this->archive->deleteFile($file, $request->user()->id);

        return back()->with('success', 'Archivo enviado a la papelera.');
    }
}
