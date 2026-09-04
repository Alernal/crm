<x-guest-layout>
    <div class="mb-8">
        <h1 class="text-[22px] font-bold text-[var(--text-900)]">Confirmar contraseña</h1>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">
            {{ __('Esta es un área segura de la aplicación. Confirma tu contraseña antes de continuar.') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />

            <x-text-input id="password" class="mt-1"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <button type="submit"
                class="w-full h-10 flex items-center justify-center rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium text-[14px] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]">
            {{ __('Confirmar') }}
        </button>
    </form>
</x-guest-layout>
