@props([
    'label',
    'value',
    'meta' => null,
    'icon' => 'chart-bar',
    'tone' => 'brand',
    'href' => null,
])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600 dark:bg-brand-900/30',
        'leaf'  => 'bg-leaf-50 text-leaf-600 dark:bg-leaf-900/30',
        'amber' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30',
        'sky'   => 'bg-sky-50 text-sky-600 dark:bg-sky-900/30',
    ];
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" wire:navigate @endif
    {{ $attributes->merge(['class' => 'block rounded-xl border border-zinc-200 p-5 transition dark:border-zinc-700'.($href ? ' hover:border-brand-400 hover:shadow-sm' : '')]) }}
>
    <div class="flex items-start justify-between">
        <div>
            <p class="text-sm text-zinc-500">{{ $label }}</p>
            <p class="mt-1 text-3xl font-bold tracking-tight">{{ number_format($value) }}</p>
        </div>

        <span class="rounded-lg p-2.5 {{ $tones[$tone] ?? $tones['brand'] }}">
            <flux:icon :name="$icon" class="size-5" />
        </span>
    </div>

    @if ($meta)
        <p class="mt-3 text-xs text-zinc-500">{{ $meta }}</p>
    @endif
</{{ $tag }}>
