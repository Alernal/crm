<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-[22px] font-semibold text-[var(--text-900)]">Recuperar contraseña</h2>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autofocus
                placeholder="contador@ejemplo.com"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <button type="submit"
                class="w-full h-10 flex items-center justify-center rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium text-[14px] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]">
            Enviar enlace de recuperación
        </button>

        <p class="text-center text-[14px] text-[var(--text-500)]">
            <a href="{{ route('login') }}" class="text-[var(--color-primary)] hover:underline font-medium">
                ← Volver al inicio de sesión
            </a>
        </p>

    </form>

</x-guest-layout>
