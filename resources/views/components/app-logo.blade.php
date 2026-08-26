@props([
    'sidebar' => false,
])

@php
    $brand = $sidebar ? 'flux:sidebar.brand' : 'flux:brand';
@endphp

@if ($sidebar)
    <flux:sidebar.brand :name="config('site.name', config('app.name'))" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white">
            <x-app-logo-icon class="size-7" />
        </x-slot>
    </flux:sidebar.brand>
@else
    <flux:brand :name="config('site.name', config('app.name'))" {{ $attributes }}>
        <x-slot name="logo" class="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-white">
            <x-app-logo-icon class="size-7" />
        </x-slot>
    </flux:brand>
@endif
