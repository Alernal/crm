<x-guest-layout>

    <div class="mb-8">
        <h2 class="text-[22px] font-semibold text-[var(--text-900)]">Bienvenido de nuevo</h2>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">Ingresa a tu cuenta para continuar</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" :value="__('Correo electrónico')" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autofocus autocomplete="username"
                placeholder="contador@ejemplo.com"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="__('Contraseña')" />
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-[13px] text-[var(--color-primary)] hover:underline font-medium">
                        ¿Olvidaste tu contraseña?
                    </a>
                @endif
            </div>
            <x-text-input
                id="password" name="password" type="password"
                required autocomplete="current-password"
                placeholder="••••••••"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('password')" class="mt-1" />
        </div>

        <div class="flex items-center gap-2">
            <input id="remember_me" name="remember" type="checkbox"
                   class="rounded border-[var(--border-strong)] text-[var(--color-primary)] focus:ring-[var(--color-primary-light)] w-4 h-4" />
            <label for="remember_me" class="text-[14px] text-[var(--text-700)]">Recordar sesión</label>
        </div>

        <button type="submit"
                class="w-full h-10 flex items-center justify-center rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium text-[14px] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]">
            Ingresar
        </button>

        <p class="text-center text-[14px] text-[var(--text-500)]">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" class="text-[var(--color-primary)] hover:underline font-medium">Regístrate gratis</a>
        </p>

    </form>

</x-guest-layout>
