<x-modal name="nuevo-canal" :show="$errors->has('name') || $errors->has('description')" maxWidth="md">
    <form method="POST" action="{{ route('communications.store') }}" class="p-6 space-y-4">
        @csrf
        <div>
            <h2 class="text-[16px] font-bold text-[var(--text-900)]">Nuevo canal</h2>
            <p class="text-[13px] text-[var(--text-500)] mt-1">Crea un canal general para tu equipo.</p>
        </div>

        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input id="name" name="name" class="mt-1 block w-full" required placeholder="tributario" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="description" value="Descripción (opcional)" />
            <x-text-input id="description" name="description" class="mt-1 block w-full" placeholder="Novedades y consultas tributarias" />
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <x-secondary-button type="button" @click="$dispatch('close-modal', 'nuevo-canal')">Cancelar</x-secondary-button>
            <button type="submit"
                    class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                Crear canal
            </button>
        </div>
    </form>
</x-modal>
