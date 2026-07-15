<x-app-layout>
<x-slot name="title">Mi Perfil</x-slot>

@php
    $inputClass = 'w-full h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] text-[var(--text-700)] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-primary-light)] focus:border-[var(--color-primary)] outline-none';
    $labelClass = 'block text-[13px] font-medium text-[var(--text-700)] mb-1.5';
@endphp

<div class="max-w-4xl mx-auto space-y-5">

    {{-- ── Header ── --}}
    <p class="text-[14px] text-[var(--text-500)] mb-2">Configura tus datos profesionales y de facturación</p>

    {{-- ── Tarjeta de identidad ── --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)] shadow-[var(--shadow-card)] p-6 flex items-center gap-5">
        <div class="w-14 h-14 rounded-full bg-[var(--color-primary-light)] flex items-center justify-center text-[var(--color-primary)] text-[18px] font-semibold flex-shrink-0">
            {{ strtoupper(substr($user->name, 0, 2)) }}
        </div>
        <div>
            <h2 class="text-[20px] font-semibold text-[var(--text-900)]">{{ $user->name }}</h2>
            <p class="text-[14px] text-[var(--text-500)] mt-0.5">{{ $user->email }}</p>
            @if($user->professional_card_number)
            <p class="text-[12px] text-[var(--color-primary)] font-medium mt-1">T.P. {{ $user->professional_card_number }}</p>
            @endif
        </div>
    </div>

    {{-- ── Logo delete (fuera del form principal para evitar anidación) ── --}}
    @if($user->logo_path)
    <form id="logo-delete-form" method="POST" action="{{ route('profile.logo.destroy') }}" class="hidden">
        @csrf @method('DELETE')
    </form>
    @endif

    {{-- ── Formulario principal (envuelve las 3 tarjetas de datos) ── --}}
    <form method="POST" action="{{ route('profile.update') }}" class="space-y-5" enctype="multipart/form-data">
        @csrf
        @method('PATCH')

        {{-- ── Información personal y profesional ── --}}
        <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)]">
            <div class="px-6 py-4 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Información personal y profesional</h2>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">Datos que aparecerán en tus cuentas de cobro y documentos.</p>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div class="sm:col-span-2">
                    <label for="name" class="{{ $labelClass }}">Nombre completo <span class="text-[var(--color-danger)]">*</span></label>
                    <input id="name" name="name" type="text"
                           value="{{ old('name', $user->name) }}" required
                           class="{{ $inputClass }}" />
                    @error('name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="email" class="{{ $labelClass }}">Correo electrónico <span class="text-[var(--color-danger)]">*</span></label>
                    <input id="email" name="email" type="email"
                           value="{{ old('email', $user->email) }}" required
                           class="{{ $inputClass }}" />
                    @error('email')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="nit" class="{{ $labelClass }}">NIT del contador</label>
                    <input id="nit" name="nit" type="text"
                           value="{{ old('nit', $user->nit) }}"
                           placeholder="Ej. 900123456-7"
                           class="{{ $inputClass }}" />
                    @error('nit')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="professional_card_number" class="{{ $labelClass }}">
                        Tarjeta profesional
                        <span class="text-[12px] text-[var(--text-400)] font-normal">(JCC)</span>
                    </label>
                    <input id="professional_card_number" name="professional_card_number" type="text"
                           value="{{ old('professional_card_number', $user->professional_card_number) }}"
                           placeholder="Ej. 12345-T"
                           class="{{ $inputClass }}" />
                    @error('professional_card_number')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="phone" class="{{ $labelClass }}">Teléfono / Celular</label>
                    <input id="phone" name="phone" type="text"
                           value="{{ old('phone', $user->phone) }}"
                           placeholder="Ej. 300 123 4567"
                           class="{{ $inputClass }}" />
                    @error('phone')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="city" class="{{ $labelClass }}">Ciudad</label>
                    <input id="city" name="city" type="text"
                           value="{{ old('city', $user->city) }}"
                           placeholder="Ej. Bogotá D.C."
                           class="{{ $inputClass }}" />
                    @error('city')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="address" class="{{ $labelClass }}">Dirección de oficina</label>
                    <input id="address" name="address" type="text"
                           value="{{ old('address', $user->address) }}"
                           placeholder="Calle, carrera, número, conjunto..."
                           class="{{ $inputClass }}" />
                    @error('address')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

            </div>
        </div>

        {{-- ── Logo para cuentas de cobro ── --}}
        <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)]"
             x-data="{ preview: '{{ $user->logo_path ? Storage::url($user->logo_path) : '' }}' }">
            <div class="px-6 py-4 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Logo para cuentas de cobro</h2>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">Opcional · PNG, JPG o WEBP · máx. 2 MB</p>
            </div>
            <div class="px-6 py-5">
                <div class="flex items-start gap-6">
                    <div class="flex-shrink-0">
                        <p class="text-[12px] text-[var(--text-400)] mb-1.5">Vista previa en PDF</p>
                        <div class="flex items-center justify-center bg-[var(--surface-subtle)] border border-dashed border-[var(--border-strong)] rounded-[var(--radius-control)] overflow-hidden"
                             style="width:160px; height:80px;">
                            <template x-if="preview">
                                <img :src="preview" class="max-w-full max-h-full object-contain" alt="Preview logo" />
                            </template>
                            <template x-if="!preview">
                                <span class="text-[12px] text-[var(--text-400)]">Sin logo</span>
                            </template>
                        </div>
                    </div>
                    <div class="flex-1 pt-5">
                        <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp"
                               x-on:change="preview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : preview"
                               class="block w-full text-[13px] text-[var(--text-500)] file:mr-3 file:py-1.5 file:px-4 file:rounded-[var(--radius-control)] file:border file:border-[var(--border-default)] file:text-[13px] file:font-medium file:bg-[var(--surface-muted)] file:text-[var(--text-700)] hover:file:bg-[var(--border-default)] file:cursor-pointer" />
                        @error('logo')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                        <p class="mt-2 text-[12px] text-[var(--text-400)]">El logo se ajustará dentro del espacio 2:1 del encabezado del PDF. Para mejores resultados usa una imagen apaisada con fondo transparente o blanco.</p>
                        @if($user->logo_path)
                        <button type="submit" form="logo-delete-form"
                                x-on:click="preview = ''"
                                class="mt-2 text-[12px] text-[var(--color-danger)] hover:underline">
                            Eliminar logo actual
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Datos bancarios ── --}}
        <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)]">
            <div class="px-6 py-4 border-b border-[var(--border-default)]">
                <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Datos bancarios para cobros</h2>
                <p class="text-[12px] text-[var(--text-400)] mt-0.5">Aparecen en el PDF de la cuenta de cobro.</p>
            </div>
            <div class="px-6 py-5 grid grid-cols-1 sm:grid-cols-2 gap-5">

                <div>
                    <label for="bank_name" class="{{ $labelClass }}">Banco</label>
                    <input id="bank_name" name="bank_name" type="text"
                           value="{{ old('bank_name', $user->bank_name) }}"
                           placeholder="Ej. Bancolombia"
                           class="{{ $inputClass }}" />
                    @error('bank_name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="account_type" class="{{ $labelClass }}">Tipo de cuenta</label>
                    <select id="account_type" name="account_type" class="{{ $inputClass }}">
                        <option value="">— Selecciona —</option>
                        <option value="savings"  {{ old('account_type', $user->account_type) === 'savings'  ? 'selected' : '' }}>Ahorros</option>
                        <option value="checking" {{ old('account_type', $user->account_type) === 'checking' ? 'selected' : '' }}>Corriente</option>
                    </select>
                    @error('account_type')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="account_number" class="{{ $labelClass }}">Número de cuenta</label>
                    <input id="account_number" name="account_number" type="text"
                           value="{{ old('account_number', $user->account_number) }}"
                           placeholder="Ej. 12345678901"
                           class="{{ $inputClass }}" />
                    @error('account_number')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="account_holder_name" class="{{ $labelClass }}">Nombre del titular</label>
                    <input id="account_holder_name" name="account_holder_name" type="text"
                           value="{{ old('account_holder_name', $user->account_holder_name) }}"
                           placeholder="Nombre completo"
                           class="{{ $inputClass }}" />
                    @error('account_holder_name')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="account_holder_id" class="{{ $labelClass }}">
                        CC / NIT del titular
                        <span class="text-[12px] text-[var(--text-400)] font-normal">(persona natural o jurídica)</span>
                    </label>
                    <input id="account_holder_id" name="account_holder_id" type="text"
                           value="{{ old('account_holder_id', $user->account_holder_id) }}"
                           placeholder="Cédula o NIT del titular"
                           class="{{ $inputClass }}" />
                    @error('account_holder_id')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                </div>

                <div class="sm:col-span-2">
                    <label for="payment_link" class="{{ $labelClass }}">
                        Link de pago en línea
                        <span class="text-[12px] text-[var(--text-400)] font-normal">(Wompi, PSE, datáfono virtual, wallet…)</span>
                    </label>
                    <input id="payment_link" name="payment_link" type="text"
                           value="{{ old('payment_link', $user->payment_link) }}"
                           placeholder="https://checkout.wompi.co/l/..."
                           class="{{ $inputClass }} font-mono" />
                    @error('payment_link')<p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>@enderror
                    <p class="mt-1.5 text-[12px] text-[var(--text-400)]">
                        Aparece en el PDF como "Otros medios de pago" con los logos de las franquicias de pago (Visa, Mastercard, Amex, PSE, Nequi, Daviplata).
                    </p>
                </div>

            </div>
        </div>

        {{-- ── Botón guardar ── --}}
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
                Guardar cambios
            </button>
            @if(session('status') === 'profile-updated')
            <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
               class="text-[14px] text-[var(--color-success)] font-medium flex items-center gap-1">
                <x-lucide-check-circle class="w-4 h-4" />
                Guardado correctamente
            </p>
            @endif
        </div>

    </form>

    {{-- ── Cambiar contraseña ── --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)]">
        <div class="px-6 py-4 border-b border-[var(--border-default)]">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Cambiar contraseña</h2>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">Usa una contraseña larga y difícil de adivinar.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="px-6 py-5 space-y-5">
            @csrf
            @method('PUT')

            <div class="max-w-sm space-y-4">
                <div>
                    <label for="current_password" class="{{ $labelClass }}">Contraseña actual</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password"
                           class="{{ $inputClass }}" />
                    @error('current_password', 'updatePassword')
                    <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="new_password" class="{{ $labelClass }}">Nueva contraseña</label>
                    <input id="new_password" name="password" type="password" autocomplete="new-password"
                           class="{{ $inputClass }}" />
                    @error('password', 'updatePassword')
                    <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="{{ $labelClass }}">Confirmar contraseña</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                           class="{{ $inputClass }}" />
                    @error('password_confirmation', 'updatePassword')
                    <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="flex items-center gap-4">
                <button type="submit"
                        class="h-10 px-5 bg-[var(--color-primary)] hover:bg-[var(--color-primary-hover)] text-white text-[14px] font-medium rounded-[var(--radius-control)]">
                    Actualizar contraseña
                </button>
                @if(session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 3000)"
                   class="text-[14px] text-[var(--color-success)] font-medium flex items-center gap-1">
                    <x-lucide-check-circle class="w-4 h-4" />
                    Contraseña actualizada
                </p>
                @endif
            </div>
        </form>
    </div>

    {{-- ── Documentos (acceso rápido) ── --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--border-default)]">
        <div class="px-6 py-4 border-b border-[var(--border-default)]">
            <h2 class="text-[16px] font-semibold text-[var(--text-900)]">Documentos de identidad profesional</h2>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">Cédula y tarjeta profesional para generar documentos con marca de agua.</p>
        </div>
        <div class="px-6 py-5 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-[var(--color-primary-light)] rounded-[var(--radius-control)] flex items-center justify-center">
                    <x-lucide-shield-check class="w-5 h-5 text-[var(--color-primary)]" />
                </div>
                <div>
                    <p class="text-[14px] font-semibold text-[var(--text-900)]">
                        {{ $user->documents()->count() }}
                        {{ $user->documents()->count() === 1 ? 'documento cargado' : 'documentos cargados' }}
                    </p>
                    <p class="text-[12px] text-[var(--text-400)]">Cédula y/o tarjeta profesional</p>
                </div>
            </div>
            <a href="{{ Route::has('documents.index') ? route('documents.index') : '#' }}"
               class="inline-flex items-center h-10 px-4 rounded-[var(--radius-control)] border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium hover:bg-[var(--surface-muted)]">
                Gestionar documentos →
            </a>
        </div>
    </div>

    {{-- ── Zona de peligro ── --}}
    <div class="bg-[var(--surface-card)] rounded-[var(--radius-card)] border border-[var(--color-danger)]/20">
        <div class="px-6 py-4 border-b border-[var(--color-danger)]/20">
            <h2 class="text-[16px] font-semibold text-[var(--color-danger)]">Zona de peligro</h2>
            <p class="text-[12px] text-[var(--text-400)] mt-0.5">Eliminar la cuenta es una acción permanente e irreversible.</p>
        </div>
        <div class="px-6 py-5">
            <button
                x-data=""
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
                class="inline-flex items-center h-10 px-4 rounded-[var(--radius-control)] border border-[var(--color-danger)]/30 text-[var(--color-danger)] text-[14px] font-medium hover:bg-[var(--color-danger-bg)]">
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

        <h2 class="text-[16px] font-semibold text-[var(--text-900)]">¿Eliminar cuenta permanentemente?</h2>
        <p class="mt-2 text-[14px] text-[var(--text-500)]">
            Esta acción eliminará todos tus datos: clientes, cuentas de cobro, documentos y configuración. No se puede deshacer.
        </p>

        <div class="mt-5">
            <label for="password_delete" class="{{ $labelClass }}">
                Confirma ingresando tu contraseña
            </label>
            <input id="password_delete" name="password" type="password"
                   placeholder="Tu contraseña actual"
                   class="w-full max-w-xs h-10 border border-[var(--border-default)] rounded-[var(--radius-control)] px-3.5 text-[14px] bg-[var(--surface-card)] focus:ring-2 focus:ring-[var(--color-danger-bg)] focus:border-[var(--color-danger)] outline-none" />
            @error('password', 'userDeletion')
            <p class="mt-1 text-[12px] text-[var(--color-danger)]">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-5 flex gap-3">
            <button type="submit"
                    class="h-10 px-4 bg-[var(--color-danger)] hover:opacity-90 text-white text-[14px] font-medium rounded-[var(--radius-control)]">
                Sí, eliminar cuenta
            </button>
            <button type="button" x-on:click="$dispatch('close')"
                    class="h-10 px-4 border border-[var(--border-default)] text-[var(--text-700)] text-[14px] font-medium rounded-[var(--radius-control)] hover:bg-[var(--surface-muted)]">
                Cancelar
            </button>
        </div>
    </form>
</x-modal>

</x-app-layout>
