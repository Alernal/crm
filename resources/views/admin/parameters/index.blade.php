@extends('admin.layout')
@section('title', 'Parámetros Globales')
@section('page-title', 'Parámetros Globales del Sistema')

@section('content')

@foreach ($parameters as $group => $params)
    <div class="mb-5">
        <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-[0.12em] mb-2.5 px-0.5">{{ ucfirst($group) }}</p>
        <div class="bg-white border border-gray-200 rounded-xl shadow-sm divide-y divide-gray-200 overflow-hidden">
            @foreach ($params as $param)
                <div class="px-6 py-4 flex items-start gap-6" x-data="{ editing: false }">
                    <div class="flex-1 min-w-0">

                        <div class="flex items-center gap-2 mb-0.5 flex-wrap">
                            <p class="font-semibold text-gray-800 text-sm">{{ $param->label }}</p>
                            <span class="mono text-[10px] bg-gray-100 text-gray-400 px-1.5 py-0.5 rounded">{{ $param->type }}</span>
                            @unless ($param->editable)
                                <span class="text-[10px] bg-amber-50 text-amber-600 border border-amber-200 px-1.5 py-0.5 rounded font-semibold">solo lectura</span>
                            @endunless
                        </div>

                        @if ($param->description)
                            <p class="text-xs text-gray-400 mb-2">{{ $param->description }}</p>
                        @endif

                        <div x-show="!editing" class="flex items-center gap-2">
                            @if ($param->type === 'color')
                                <span class="w-5 h-5 rounded-md border border-gray-200 inline-block shrink-0"
                                      style="background:{{ $param->value }}"></span>
                            @endif
                            <span class="text-sm mono {{ $param->value ? 'text-gray-700' : 'text-gray-300 italic' }}">
                                {{ $param->value ?: '(vacío)' }}
                            </span>
                        </div>

                        @if ($param->editable)
                            <form method="POST" action="{{ route('admin.parameters.update', $param) }}"
                                  x-show="editing" class="flex items-center gap-3 mt-2" x-cloak>
                                @csrf @method('PATCH')
                                @if ($param->type === 'boolean')
                                    <select name="value"
                                            class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                                        <option value="1" {{ $param->value === '1' ? 'selected' : '' }}>Sí</option>
                                        <option value="0" {{ $param->value !== '1' ? 'selected' : '' }}>No</option>
                                    </select>
                                @elseif ($param->type === 'color')
                                    <input type="color" name="value" value="{{ $param->value ?: '#000000' }}"
                                           class="h-9 w-16 rounded-lg border border-gray-200 cursor-pointer">
                                @else
                                    <input type="{{ $param->type === 'number' ? 'number' : 'text' }}"
                                           name="value" value="{{ $param->value }}"
                                           class="border border-gray-200 rounded-lg px-3 py-1.5 text-sm w-64 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400/40 focus:border-blue-400">
                                @endif
                                <button type="submit"
                                        class="bg-blue-800 hover:bg-blue-700 text-white text-xs font-semibold px-3 py-1.5 rounded-lg transition-colors shadow-sm">
                                    Guardar
                                </button>
                                <button type="button" @click="editing = false"
                                        class="text-xs text-gray-400 hover:text-gray-600 font-medium">Cancelar</button>
                            </form>
                        @endif
                    </div>

                    @if ($param->editable)
                        <button @click="editing = !editing"
                                class="shrink-0 text-xs font-medium text-blue-700 hover:text-blue-600 mt-0.5">
                            Editar
                        </button>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endforeach

@endsection
