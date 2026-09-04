@props(['title' => 'Comunicación'])

@auth('web')
    <x-app-layout>
        <x-slot name="title">{{ $title }}</x-slot>
        {{ $slot }}
    </x-app-layout>
@else
    <x-team-member-layout :title="$title">
        <div class="h-full p-4 lg:p-6">
            {{ $slot }}
        </div>
    </x-team-member-layout>
@endauth
