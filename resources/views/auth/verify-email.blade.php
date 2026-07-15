<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-[22px] font-semibold text-[var(--text-900)]">Verifica tu correo</h2>
        <p class="mt-2 text-[var(--text-500)] text-[14px]">
            {{ __('¡Gracias por registrarte! Antes de comenzar, ¿podrías verificar tu correo electrónico haciendo clic en el enlace que acabamos de enviarte? Si no recibiste el correo, con gusto te enviaremos otro.') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 font-medium text-[14px] text-[var(--color-success)]">
            {{ __('Se ha enviado un nuevo enlace de verificación al correo que registraste.') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                    class="h-10 px-5 rounded-[var(--radius-control)] bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white font-medium text-[14px] focus:outline-none focus:ring-2 focus:ring-[var(--color-primary-light)]">
                {{ __('Reenviar correo de verificación') }}
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-[14px] text-[var(--text-500)] hover:text-[var(--text-900)] hover:underline focus:outline-none">
                {{ __('Cerrar sesión') }}
            </button>
        </form>
    </div>
</x-guest-layout>
