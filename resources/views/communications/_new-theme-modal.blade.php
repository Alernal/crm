<x-modal name="nuevo-tema" :show="$errors->has('name') || $errors->has('description')" maxWidth="md">
    <form method="POST" action="{{ route('communications.themes.store', $channel) }}" class="p-6 space-y-4">
        @csrf
        <div>
            <h2 class="text-[16px] font-bold text-[var(--text-900)]">Nuevo tema</h2>
            <p class="text-[13px] text-[var(--text-500)] mt-1">Agrupa la conversación de este canal en un tema específico.</p>
        </div>

        <div>
            <x-input-label for="theme-name" value="Nombre" />
            <x-text-input id="theme-name" name="name" class="mt-1 block w-full" required placeholder="Revisión Legal" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="theme-description" value="Descripción (opcional)" />
            <x-text-input id="theme-description" name="description" class="mt-1 block w-full" placeholder="Cláusulas y condiciones a revisar" />
            <x-input-error :messages="$errors->get('description')" class="mt-1" />
        </div>

        <div class="flex justify-end gap-2 pt-2">
            <x-secondary-button type="button" @click="$dispatch('close-modal', 'nuevo-tema')">Cancelar</x-secondary-button>
            <button type="submit"
                    class="inline-flex items-center gap-[6px] h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white rounded-[var(--radius-control)] text-[14px] font-medium">
                Crear tema
            </button>
        </div>
    </form>
</x-modal>
