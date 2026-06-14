<x-app-layout>
<x-slot name="title">Mi Perfil</x-slot>

<div class="max-w-4xl space-y-5">

    {{-- ===== CABECERA DE PERFIL ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 flex items-center gap-5">
        <div class="w-16 h-16 rounded-2xl bg-blue-700 flex items-center justify-center text-white text-xl font-bold flex-shrink-0">
            {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div>
            <h2 class="text-xl font-bold text-gray-900">{{ $user->name }}</h2>
            <p class="text-sm text-gray-500 mt-0.5">{{ $user->email }}</p>
            @if($user->professional_card_number)
            <p class="text-xs text-blue-600 font-medium mt-1">T.P. {{ $user->professional_card_number }}</p>
            @endif
        </div>
    </div>

    {{-- ===== INFORMACIÓN PERSONAL Y PROFESIONAL ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Información personal y profesional</h3>
            <p class="text-sm text-gray-500 mt-0.5">Datos que aparecerán en tus cuentas de cobro y documentos.</p>
        </div>

        <form method="POST" action="{{ route('profile.update') }}" class="px-6 py-5 space-y-5">
            @csrf
            @method('PATCH')

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Nombre completo <span class="text-red-500">*</span></label>
                    <input id="name" name="name" type="text"
                           value="{{ old('name', $user->name) }}" required
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico <span class="text-red-500">*</span></label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email', $user->email) }}" required
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('email')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nit" class="block text-sm font-medium text-gray-700 mb-1">NIT del contador</label>
                    <input id="nit" name="nit" type="text"
                           value="{{ old('nit', $user->nit) }}"
                           placeholder="Ej. 900123456-7"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('nit')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="professional_card_number" class="block text-sm font-medium text-gray-700 mb-1">
                        Número de tarjeta profesional
                        <span class="text-xs text-gray-400 font-normal">(JCC)</span>
                    </label>
                    <input id="professional_card_number" name="professional_card_number" type="text"
                           value="{{ old('professional_card_number', $user->professional_card_number) }}"
                           placeholder="Ej. 12345-T"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('professional_card_number')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Teléfono / Celular</label>
                    <input id="phone" name="phone" type="text"
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="Ej. 300 123 4567"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('phone')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">Ciudad</label>
                    <input id="city" name="city" type="text"
                           value="{{ old('city', $user->city) }}"
                           placeholder="Ej. Bogotá D.C."
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('city')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Dirección de oficina</label>
                    <input id="address" name="address" type="text"
                           value="{{ old('address', $user->address) }}"
                           placeholder="Calle, carrera, número, conjunto..."
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('address')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

            </div>

            <div class="flex items-center gap-4 pt-1">
                <button type="submit"
                        class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Guardar cambios
                </button>
                @if(session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="text-sm text-emerald-600 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Guardado correctamente
                </p>
                @endif
            </div>
        </form>
    </div>

    {{-- ===== CAMBIAR CONTRASEÑA ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Cambiar contraseña</h3>
            <p class="text-sm text-gray-500 mt-0.5">Usa una contraseña larga y difícil de adivinar.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="px-6 py-5 space-y-5">
            @csrf
            @method('PUT')

            <div class="max-w-sm space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña actual</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('current_password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="block text-sm font-medium text-gray-700 mb-1">Nueva contraseña</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('password', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" />
                    @error('password_confirmation', 'updatePassword')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="px-5 py-2 bg-blue-700 hover:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-colors">
                    Actualizar contraseña
                </button>
                @if(session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="text-sm text-emerald-600 font-medium flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Contraseña actualizada
                </p>
                @endif
            </div>
        </form>
    </div>

    {{-- ===== DOCUMENTOS (acceso rápido) ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Documentos de identidad profesional</h3>
            <p class="text-sm text-gray-500 mt-0.5">Cédula y tarjeta profesional para generar documentos con marca de agua.</p>
        </div>
        <div class="px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $user->documents()->count() }}
                        {{ $user->documents()->count() === 1 ? 'documento cargado' : 'documentos cargados' }}
                    </p>
                    <p class="text-xs text-gray-500">Cédula y/o tarjeta profesional</p>
                </div>
            </div>
            <a href="{{ Route::has('documents.index') ? route('documents.index') : '#' }}"
               class="px-4 py-2 text-sm font-medium text-blue-700 border border-blue-200 rounded-lg hover:bg-blue-50 transition-colors">
                Gestionar documentos →
            </a>
        </div>
    </div>

    {{-- ===== ZONA DE PELIGRO ===== --}}
    <div class="bg-white rounded-xl shadow-sm border border-red-100">
        <div class="px-6 py-4 border-b border-red-100">
            <h3 class="font-semibold text-red-700">Zona de peligro</h3>
            <p class="text-sm text-gray-500 mt-0.5">Eliminar la cuenta es una acción permanente e irreversible.</p>
        </div>
        <div class="px-6 py-5">
            <button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Eliminar mi cuenta
            </button>
        </div>
    </div>

</div>

{{-- Modal confirmación eliminación --}}
<x-modal name="confirm-user-deletion" focusable>
    <form method="POST" action="{{ route('profile.destroy') }}" class="p-6">
        @csrf
        @method('DELETE')

        <h2 class="text-base font-bold text-gray-900">¿Eliminar cuenta permanentemente?</h2>
        <p class="mt-2 text-sm text-gray-600">
            Esta acción eliminará todos tus datos: clientes, cuentas de cobro, documentos y configuración. No se puede deshacer.
        </p>

        <div class="mt-5">
            <label for="password_delete" class="block text-sm font-medium text-gray-700 mb-1">
                Confirma ingresando tu contraseña
            </label>
            <input id="password_delete" name="password" type="password"
                   placeholder="Tu contraseña actual"
                   class="w-full max-w-xs rounded-lg border-gray-300 shadow-sm text-sm focus:border-red-500 focus:ring-red-500" />
            @error('password', 'userDeletion')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-5 flex gap-3">
            <button type="submit"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-semibold rounded-lg transition-colors">
                Sí, eliminar cuenta
            </button>
            <button type="button" x-on:click="$dispatch('close')"
                    class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
                Cancelar
            </button>
        </div>
    </form>
</x-modal>

</x-app-layout>
