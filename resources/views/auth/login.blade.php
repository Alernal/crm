<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Bienvenido de nuevo</h2>
        <p class="mt-2 text-gray-500 text-sm">Ingresa a tu cuenta para continuar</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autofocus autocomplete="username"
                placeholder="contador@ejemplo.com"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" class="text-gray-700 font-medium text-sm" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <x-text-input
                id="password" name="password" type="password"
                required autocomplete="current-password"
                placeholder="••••••••"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="flex items-center gap-2">
            <input id="remember_me" name="remember" type="checkbox"
                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 w-4 h-4" />
            <label for="remember_me" class="text-sm text-gray-600">Recordar sesión</label>
        </div>

        <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Ingresar
        </button>

        <p class="text-center text-sm text-gray-500">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 font-medium">Regístrate gratis</a>
        </p>

    </form>

</x-guest-layout>
