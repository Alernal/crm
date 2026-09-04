<x-guest-layout>

    <div class="mb-8">
        <h1 class="text-[22px] font-bold text-[var(--text-900)]">Acceso de equipo</h1>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">Ingresa con las credenciales que te compartió tu contador.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('team-member.login.store') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input
                id="email" name="email" type="email"
                :value="old('email')"
                required autofocus autocomplete="username"
                placeholder="tu@correo.com"
                class="mt-1"
            />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
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
            ¿Eres el contador dueño de la cuenta?
            <a href="{{ route('login') }}" class="text-[var(--color-primary)] hover:underline font-medium">Inicia sesión aquí</a>
        </p>

    </form>

</x-guest-layout>
