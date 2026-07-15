<x-app-layout :title="'Archivo Virtual — ' . $client->name">

<div class="max-w-7xl mx-auto space-y-4"
     x-data="archiveExplorer({{ json_encode($tree) }}, {{ $folder?->id ?? 'null' }})">

    {{-- ENCABEZADO --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-[22px] font-semibold text-[var(--text-900)]">Archivo Virtual</h1>
            <p class="text-[14px] text-[var(--text-500)]">{{ $client->name }} · {{ $client->full_document }}</p>
        </div>
        <a href="{{ route('finance.client', $client) }}"
           class="text-[13px] text-[var(--color-primary)] hover:underline">← Finanzas del cliente</a>
    </div>

    {{-- ALERTAS --}}
    @if(session('success'))
    <div class="bg-[var(--color-success-bg)] border border-[var(--color-success)]/20 text-[var(--color-success-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">{{ session('success') }}</div>
    @endif
    @if($errors->any())
    <div class="bg-[var(--color-danger-bg)] border border-[var(--color-danger)]/20 text-[var(--color-danger-text)] text-[14px] px-4 py-3 rounded-[var(--radius-control)]">{{ $errors->first() }}</div>
    @endif

    <div class="flex gap-4 h-[calc(100vh-220px)]">

        {{-- ÁRBOL DE CARPETAS --}}
        <aside class="w-64 flex-shrink-0 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-y-auto">
            <div class="px-3 py-2.5 border-b border-[var(--border-default)] text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">
                Carpetas
            </div>
            <div class="p-2">
                <template x-for="node in tree" :key="node.id">
                    <div x-html="renderNode(node, 0)"></div>
                </template>
            </div>
        </aside>

        {{-- PANEL PRINCIPAL --}}
        <div class="flex-1 flex flex-col gap-3 min-w-0">

            {{-- BREADCRUMB --}}
            <nav class="flex items-center gap-1 text-[14px] text-[var(--text-500)]">
                <a href="{{ route('archive.files.index', $client) }}"
                   class="hover:text-[var(--color-primary)] font-medium">Raíz</a>
                @foreach($breadcrumbs as $crumb)
                    <x-lucide-chevron-right class="w-3.5 h-3.5 text-[var(--text-400)]" />
                    <a href="{{ route('archive.files.index', [$client, 'folder' => $crumb->id]) }}"
                       class="hover:text-[var(--color-primary)] {{ $loop->last ? 'text-[var(--text-900)] font-medium' : '' }}">
                        {{ $crumb->name }}
                    </a>
                @endforeach
            </nav>

            {{-- BARRA DE ACCIONES --}}
            <div class="flex items-center gap-2">
                {{-- Subir archivo --}}
                <button @click="$refs.uploadModal.classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 h-9 px-3.5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[13px] font-medium rounded-[var(--radius-control)]">
                    <x-lucide-upload class="w-4 h-4" />
                    Subir archivo
                </button>

                {{-- Nueva carpeta --}}
                <button @click="$refs.folderModal.classList.remove('hidden')"
                        class="inline-flex items-center gap-1.5 h-9 px-3.5 border border-[var(--border-default)] text-[var(--text-700)] text-[13px] font-medium rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)]">
                    <x-lucide-folder class="w-4 h-4" />
                    Nueva carpeta
                </button>
            </div>

            {{-- CONTENIDO: SUBCARPETAS --}}
            @if($subfolders->count())
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-2">
                @foreach($subfolders as $sub)
                <a href="{{ route('archive.files.index', [$client, 'folder' => $sub->id]) }}"
                   class="flex items-center gap-2 px-3 py-2.5 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-control)] hover:bg-[var(--color-primary-light)] hover:border-[var(--color-primary)] group">
                    <x-lucide-folder class="w-5 h-5 text-[var(--color-warning)] flex-shrink-0" />
                    <span class="text-[14px] text-[var(--text-700)] truncate">{{ $sub->name }}</span>
                    @if($sub->is_system)
                    <span class="ml-auto text-[11px] text-[var(--text-400)]">sys</span>
                    @else
                    <span class="ml-auto opacity-0 group-hover:opacity-100">
                        <button type="button"
                                onclick="event.preventDefault(); document.getElementById('del-folder-{{ $sub->id }}').submit()"
                                class="text-[var(--color-danger)] hover:opacity-80 text-[12px]">✕</button>
                        <form id="del-folder-{{ $sub->id }}"
                              method="POST"
                              action="{{ route('archive.folders.destroy', [$client, $sub]) }}"
                              class="hidden">
                            @csrf @method('DELETE')
                        </form>
                    </span>
                    @endif
                </a>
                @endforeach
            </div>
            @endif

            {{-- CONTENIDO: ARCHIVOS --}}
            @if($files->count())
            <div class="flex-1 bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-[var(--border-default)]">
                            <th class="text-left px-4 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em]">Nombre</th>
                            <th class="text-left px-4 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] hidden sm:table-cell">Tipo</th>
                            <th class="text-right px-4 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] hidden sm:table-cell">Tamaño</th>
                            <th class="text-right px-4 py-3 text-[11px] font-medium text-[var(--text-400)] uppercase tracking-[0.06em] hidden md:table-cell">Fecha</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($files as $file)
                        <tr class="border-b border-[var(--surface-muted)] hover:bg-[var(--surface-subtle)]">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-[16px]">{{ fileIcon($file->mime_type) }}</span>
                                    <a href="{{ route('archive.files.download', [$client, $file]) }}"
                                       class="text-[14px] text-[var(--color-primary)] hover:underline truncate max-w-xs">
                                        {{ $file->original_filename }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-[13px] text-[var(--text-500)] hidden sm:table-cell">{{ strtoupper($file->extension()) }}</td>
                            <td class="px-4 py-3 text-[13px] text-[var(--text-500)] text-right hidden sm:table-cell">{{ $file->humanSize() }}</td>
                            <td class="px-4 py-3 text-[13px] text-[var(--text-500)] text-right hidden md:table-cell">{{ $file->created_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-[10px]">
                                    @if($file->isPreviewable())
                                    <a href="{{ route('archive.files.download', [$client, $file]) }}"
                                       target="_blank"
                                       class="text-[var(--text-400)] hover:text-[var(--text-900)]"
                                       title="Vista previa">
                                        <x-lucide-eye class="w-4 h-4" />
                                    </a>
                                    @endif
                                    <form method="POST" action="{{ route('archive.files.destroy', [$client, $file]) }}"
                                          onsubmit="return confirm('¿Enviar a la papelera?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-[var(--color-danger)]/60 hover:text-[var(--color-danger)]" title="Eliminar">
                                            <x-lucide-trash-2 class="w-4 h-4" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="flex-1 flex items-center justify-center bg-[var(--surface-card)] border border-dashed border-[var(--border-strong)] rounded-[var(--radius-card)]">
                <div class="text-center py-12">
                    <x-lucide-file-text class="w-12 h-12 text-[var(--text-400)] mx-auto mb-3" />
                    <p class="text-[var(--text-400)] text-[14px]">Esta carpeta está vacía</p>
                    <button @click="$refs.uploadModal.classList.remove('hidden')"
                            class="mt-2 text-[var(--color-primary)] text-[13px] hover:underline">Subir primer archivo</button>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- MODAL: SUBIR ARCHIVO --}}
    <div x-ref="uploadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-md p-6">
            <h3 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Subir archivo</h3>
            <form method="POST" action="{{ route('archive.files.store', $client) }}" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="folder_id" value="{{ $folder?->id }}">
                <div class="space-y-3">
                    <div>
                        <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Archivo</label>
                        <input type="file" name="file"
                               accept=".pdf,.xlsx,.xls,.docx,.doc,.csv,.jpg,.jpeg,.png,.webp"
                               class="w-full text-[13px] text-[var(--text-500)] file:mr-3 file:py-1.5 file:px-3 file:rounded-[var(--radius-control)] file:border-0 file:bg-[var(--color-primary-light)] file:text-[var(--color-primary)]">
                        <p class="text-[12px] text-[var(--text-400)] mt-1">PDF, Excel, Word, CSV, imágenes · Máx 25 MB</p>
                    </div>
                </div>
                <div class="flex justify-end gap-2 mt-5">
                    <button type="button" @click="$refs.uploadModal.classList.add('hidden')"
                            class="h-10 px-4 text-[14px] text-[var(--text-700)] border border-[var(--border-default)] rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)]">Cancelar</button>
                    <button type="submit"
                            class="h-10 px-4 text-[14px] font-medium bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)]">Subir</button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL: NUEVA CARPETA --}}
    <div x-ref="folderModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-gray-900/50">
        <div class="bg-[var(--surface-card)] border border-[var(--border-default)] rounded-[var(--radius-card)] shadow-[var(--shadow-card-hover)] w-full max-w-sm p-6">
            <h3 class="text-[16px] font-semibold text-[var(--text-900)] mb-4">Nueva carpeta</h3>
            <form method="POST" action="{{ route('archive.folders.store', $client) }}">
                @csrf
                <input type="hidden" name="parent_id" value="{{ $folder?->id }}">
                <div>
                    <label class="block text-[13px] font-medium text-[var(--text-700)] mb-1.5">Nombre</label>
                    <input type="text" name="name" required maxlength="100" autofocus
                           class="w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none">
                </div>
                <div class="flex justify-end gap-2 mt-4">
                    <button type="button" @click="$refs.folderModal.classList.add('hidden')"
                            class="h-10 px-4 text-[14px] text-[var(--text-700)] border border-[var(--border-default)] rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)]">Cancelar</button>
                    <button type="submit"
                            class="h-10 px-4 text-[14px] font-medium bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)]">Crear</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function archiveExplorer(tree, currentFolderId) {
    return {
        tree,
        currentFolderId,
        renderNode(node, depth) {
            const indent = depth * 12;
            const isActive = node.id === this.currentFolderId;
            const url = `{{ route('archive.files.index', $client) }}?folder=${node.id}`;
            let html = `<div style="padding-left:${indent}px" class="mb-0.5">
                <a href="${url}" class="flex items-center gap-1.5 px-2 py-1.5 rounded-[var(--radius-control)] text-[14px] ${isActive ? 'bg-[var(--color-primary-light)] text-[var(--color-primary)] font-medium' : 'text-[var(--text-700)] hover:bg-[var(--surface-muted)]'}">
                    <svg class="w-4 h-4 text-[var(--color-warning)] flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/>
                    </svg>
                    <span class="truncate">${node.name}</span>
                </a>`;
            if (node.children && node.children.length > 0) {
                node.children.forEach(child => {
                    html += this.renderNode(child, depth + 1);
                });
            }
            html += '</div>';
            return html;
        }
    };
}
</script>

@php
function fileIcon(string $mime): string {
    return match(true) {
        str_starts_with($mime, 'image/') => '🖼️',
        $mime === 'application/pdf'      => '📄',
        str_contains($mime, 'spreadsheet') || str_contains($mime, 'excel') => '📊',
        str_contains($mime, 'word') || str_contains($mime, 'document') => '📝',
        $mime === 'text/csv' => '📋',
        default => '📁',
    };
}
@endphp

</x-app-layout>
