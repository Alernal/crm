<?php

namespace App\Services;

use App\Models\Client;
use App\Models\VirtualFile;
use App\Models\VirtualFileLog;
use App\Models\VirtualFolder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VirtualArchiveService
{
    const SYSTEM_FOLDERS = [
        'Información General',
        'Estados Financieros',
        'Presupuestos',
        'Evaluaciones de Inversión',
        'Declaraciones Tributarias',
        'Contratos',
        'Papeles de Trabajo',
    ];

    const MAX_FILE_SIZE_MB = 25;

    const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'text/csv',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    /**
     * Crea la carpeta raíz y subcarpetas del sistema para un cliente nuevo.
     * Se llama dentro de una transacción DB para que sea atómica con la creación del cliente.
     */
    public function createClientFolders(Client $client): VirtualFolder
    {
        $rootName = $client->archiveRootName();

        $root = VirtualFolder::create([
            'user_id'   => $client->user_id,
            'client_id' => $client->id,
            'parent_id' => null,
            'name'      => $rootName,
            'path'      => '/',
            'is_system' => true,
        ]);

        $root->path = "/{$root->id}/";
        $root->save();

        foreach (self::SYSTEM_FOLDERS as $folderName) {
            $child = VirtualFolder::create([
                'user_id'   => $client->user_id,
                'client_id' => $client->id,
                'parent_id' => $root->id,
                'name'      => $folderName,
                'path'      => "/{$root->id}/",
                'is_system' => true,
            ]);
            $child->path = "/{$root->id}/{$child->id}/";
            $child->save();
        }

        return $root;
    }

    public function createFolder(Client $client, ?int $parentId, string $name): VirtualFolder
    {
        $parentPath = '/';
        if ($parentId) {
            $parent = VirtualFolder::where('id', $parentId)
                ->where('client_id', $client->id)
                ->firstOrFail();
            $parentPath = $parent->path;
        }

        $folder = VirtualFolder::create([
            'user_id'   => $client->user_id,
            'client_id' => $client->id,
            'parent_id' => $parentId,
            'name'      => $name,
            'path'      => $parentPath,
            'is_system' => false,
        ]);

        $folder->path = $parentPath . $folder->id . '/';
        $folder->save();

        return $folder;
    }

    public function renameFolder(VirtualFolder $folder, string $newName): void
    {
        $folder->update(['name' => $newName]);
    }

    public function deleteFolder(VirtualFolder $folder): void
    {
        if ($folder->is_system) {
            throw new \RuntimeException('Las carpetas del sistema no se pueden eliminar desde aquí.');
        }
        $folder->children()->each(fn($child) => $this->deleteFolder($child));
        $folder->files()->each(fn($file) => $this->deleteFile($file, $folder->user_id));
        $folder->delete();
    }

    public function uploadFile(
        Client       $client,
        UploadedFile $upload,
        int          $userId,
        ?int         $folderId = null
    ): VirtualFile {
        $storageFilename = Str::uuid() . '.' . $upload->getClientOriginalExtension();
        $storagePath     = "archivo-virtual/{$client->user_id}/{$client->id}/{$storageFilename}";

        Storage::disk('local')->putFileAs(
            "archivo-virtual/{$client->user_id}/{$client->id}",
            $upload,
            $storageFilename
        );

        $file = VirtualFile::create([
            'user_id'           => $userId,
            'client_id'         => $client->id,
            'folder_id'         => $folderId,
            'original_filename' => $upload->getClientOriginalName(),
            'storage_filename'  => $storageFilename,
            'file_path'         => $storagePath,
            'mime_type'         => $upload->getMimeType(),
            'file_size'         => $upload->getSize(),
        ]);

        $this->log($file, $userId, 'upload');

        return $file;
    }

    public function moveFile(VirtualFile $file, ?int $newFolderId, int $userId): void
    {
        $before = ['folder_id' => $file->folder_id];
        $file->update(['folder_id' => $newFolderId]);
        $this->log($file, $userId, 'move', ['before' => $before, 'after' => ['folder_id' => $newFolderId]]);
    }

    public function renameFile(VirtualFile $file, string $newName, int $userId): void
    {
        $before = ['original_filename' => $file->original_filename];
        $file->update(['original_filename' => $newName]);
        $this->log($file, $userId, 'rename', ['before' => $before, 'after' => ['original_filename' => $newName]]);
    }

    public function deleteFile(VirtualFile $file, int $userId): void
    {
        $this->log($file, $userId, 'delete');
        $file->delete();   // soft delete — papelera 30 días
    }

    public function restoreFile(VirtualFile $file, int $userId): void
    {
        $file->restore();
        $this->log($file, $userId, 'restore');
    }

    public function permanentlyDeleteFile(VirtualFile $file): void
    {
        Storage::disk('local')->delete($file->file_path);
        $file->forceDelete();
    }

    public function getStorageResponse(VirtualFile $file)
    {
        return Storage::disk('local')->response(
            $file->file_path,
            $file->original_filename,
            ['Content-Type' => $file->mime_type]
        );
    }

    /**
     * Devuelve el árbol de carpetas para un cliente, listo para la UI.
     */
    public function getFolderTree(Client $client): array
    {
        $folders = VirtualFolder::where('client_id', $client->id)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $tree = [];
        foreach ($folders as $folder) {
            if ($folder->parent_id === null) {
                $tree[] = $this->buildTreeNode($folder, $folders);
            }
        }
        return $tree;
    }

    private function buildTreeNode(VirtualFolder $folder, $all): array
    {
        $children = $all->where('parent_id', $folder->id)->values();
        return [
            'id'        => $folder->id,
            'name'      => $folder->name,
            'is_system' => $folder->is_system,
            'children'  => $children->map(fn($c) => $this->buildTreeNode($c, $all))->values()->toArray(),
        ];
    }

    public function saveGeneratedPdf(Client $client, string $tempPath, string $standardName, string $folderName, int $userId): VirtualFile
    {
        $folder = VirtualFolder::where('client_id', $client->id)
            ->where('name', $folderName)
            ->where('is_system', true)
            ->first();

        $size    = filesize($tempPath);
        $uuid    = Str::uuid() . '.pdf';
        $dest    = "archivo-virtual/{$client->user_id}/{$client->id}/{$uuid}";

        Storage::disk('local')->put($dest, file_get_contents($tempPath));

        return VirtualFile::create([
            'user_id'           => $userId,
            'client_id'         => $client->id,
            'folder_id'         => $folder?->id,
            'original_filename' => $standardName,
            'storage_filename'  => $uuid,
            'file_path'         => $dest,
            'mime_type'         => 'application/pdf',
            'file_size'         => $size,
        ]);
    }

    private function log(VirtualFile $file, int $userId, string $action, array $details = []): void
    {
        VirtualFileLog::create([
            'user_id'         => $userId,
            'virtual_file_id' => $file->id,
            'filename'        => $file->original_filename,
            'action'          => $action,
            'details'         => $details ?: null,
            'created_at'      => now(),
        ]);
    }
}
