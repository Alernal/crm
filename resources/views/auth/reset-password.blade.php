<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-[22px] font-semibold text-[var(--text-900)]">Nueva contraseña</h2>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">Elige una contraseña segura para tu cuenta.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}" />

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email', $request->email)"
                required autofocus autocomplete="username"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Nueva contraseña')" />
            <x-text-input
                id="password" name="password" type="password"
                required autocomplete="new-password"
                placeholder="Mínimo 8 caracteres"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
            <x-text-input
                id="password_confirmation" name="password_confirmation" type="password"
                required autocomplete="new-password"
                placeholder="Repite tu nueva contraseña"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <button type="submit"
                class="w-full h-10 flex items-center justify-center rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium text-[14px] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]">
            Restablecer contraseña
        </button>

    </form>

</x-guest-layout>
