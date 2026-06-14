<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Recuperar contraseña</h2>
        <p class="mt-2 text-gray-500 text-sm">
            Ingresa tu correo y te enviaremos un enlace para restablecer tu contraseña.
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autofocus
                placeholder="contador@ejemplo.com"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Enviar enlace de recuperación
        </button>

        <p class="text-center text-sm text-gray-500">
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">
                ← Volver al inicio de sesión
            </a>
        </p>

    </form>

</x-guest-layout>
