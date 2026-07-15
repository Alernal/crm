<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-[22px] font-semibold text-[var(--text-900)]">Crea tu cuenta</h2>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">Comienza a gestionar tu práctica contable hoy</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Nombre completo')" />
            <x-text-input
                id="name" name="name" type="text"
                :value="old('name')"
                required autofocus autocomplete="name"
                placeholder="Ej. Carlos Andrés Pérez"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autocomplete="username"
                placeholder="contador@ejemplo.com"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
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
                placeholder="Repite tu contraseña"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1" />
        </div>

        <div class="bg-[var(--color-primary-light)] border border-[var(--border-default)] rounded-[var(--radius-control)] p-3 flex gap-2">
            <x-lucide-alert-triangle class="w-4 h-4 text-[var(--color-primary)] mt-0.5 flex-shrink-0" />
            <p class="text-[13px] text-[var(--color-primary-dark)]">
                Podrás completar tu perfil profesional (NIT, tarjeta profesional, ciudad) después de ingresar.
            </p>
        </div>

        <button type="submit"
                class="w-full h-10 flex items-center justify-center rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium text-[14px] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]">
            Crear cuenta
        </button>

        <p class="text-center text-[14px] text-[var(--text-500)]">
            ¿Ya tienes cuenta?
            <a href="{{ route('login') }}" class="text-[var(--color-primary)] hover:underline font-medium">Ingresa aquí</a>
        </p>

    </form>

</x-guest-layout>
