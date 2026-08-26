@props([
    'title',
    'rows',
    'labelKey',
    'valueKey',
    'empty' => 'No data yet.',
])

@php
    $peak = max(1, (int) collect($rows)->max($valueKey));
@endphp

<flux:card>
    <flux:heading size="lg">{{ $title }}</flux:heading>

    <div class="mt-5 space-y-3">
        @forelse ($rows as $row)
            <div>
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="truncate" title="{{ $row[$labelKey] ?? '—' }}">
                        {{ $row[$labelKey] ?: '(direct)' }}
                    </span>
                    <span class="shrink-0 text-zinc-500">{{ number_format($row[$valueKey] ?? 0) }}</span>
                </div>
                <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <div class="h-full rounded-full bg-brand-500"
                         style="width: {{ round(($row[$valueKey] ?? 0) / $peak * 100) }}%"></div>
                </div>
            </div>
        @empty
            <flux:text size="sm">{{ $empty }}</flux:text>
        @endforelse
    </div>
</flux:card>
