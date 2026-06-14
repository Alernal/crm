<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Crea tu cuenta</h2>
        <p class="mt-2 text-gray-500 text-sm">Comienza a gestionar tu práctica contable hoy</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nombre completo')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="name" name="name" type="text"
                :value="old('name')"
                required autofocus autocomplete="name"
                placeholder="Ej. Carlos Andrés Pérez"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autocomplete="username"
                placeholder="contador@ejemplo.com"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="password" name="password" type="password"
                required autocomplete="new-password"
                placeholder="Mínimo 8 caracteres"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="password_confirmation" name="password_confirmation" type="password"
                required autocomplete="new-password"
                placeholder="Repite tu contraseña"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="bg-blue-50 border border-blue-100 rounded-lg p-3 flex gap-2">
            <svg class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-xs text-blue-700">
                Podrás completar tu perfil profesional (NIT, tarjeta profesional, ciudad) después de ingresar.
            </p>
        </div>

        <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Crear cuenta
        </button>

        <p class="text-center text-sm text-gray-500">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-blue-600 hover:text-blue-700 font-medium">Ingresa aquí</a>
        </p>

    </form>

</x-guest-layout>
