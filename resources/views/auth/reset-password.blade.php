<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-3xl font-bold text-gray-900">Nueva contraseña</h2>
        <p class="mt-2 text-gray-500 text-sm">Elige una contraseña segura para tu cuenta.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" class="text-gray-700 font-medium text-sm" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email', $request->email)"
                required autofocus autocomplete="username"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Nueva contraseña')" class="text-gray-700 font-medium text-sm" />
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
                placeholder="Repite tu nueva contraseña"
                class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <button type="submit"
                class="w-full flex justify-center py-2.5 px-4 rounded-lg bg-blue-700 hover:bg-blue-800 text-white font-semibold text-sm shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
            Restablecer contraseña
        </button>

    </form>

</x-guest-layout>
