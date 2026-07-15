<?php

namespace App\Http\Controllers\VirtualArchive;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\VirtualFolder;
use App\Services\VirtualArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function __construct(private VirtualArchiveService $archive) {}

    public function store(Request $request, Client $client): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);

        $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'nullable|integer|exists:virtual_folders,id',
        ]);

        $this->archive->createFolder($client, $request->parent_id, $request->name);

        return back()->with('success', 'Carpeta creada.');
    }

    public function update(Request $request, Client $client, VirtualFolder $folder): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);
        abort_if($folder->client_id !== $client->id, 403);
        abort_if($folder->is_system, 403, 'Las carpetas del sistema no se pueden renombrar.');

        $request->validate(['name' => 'required|string|max:100']);

        $this->archive->renameFolder($folder, $request->name);

        return back()->with('success', 'Carpeta renombrada.');
    }

    public function destroy(Request $request, Client $client, VirtualFolder $folder): RedirectResponse
    {
        abort_if($client->user_id !== $request->user()->id, 403);
        abort_if($folder->client_id !== $client->id, 403);

        try {
            $this->archive->deleteFolder($folder);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['folder' => $e->getMessage()]);
        }

        return back()->with('success', 'Carpeta eliminada.');
    }
}
