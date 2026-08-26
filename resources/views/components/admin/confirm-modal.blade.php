@props(['pending' => null])

@php
    $variant = $pending['variant'] ?? 'danger';

    $tones = [
        'danger' => ['icon' => 'exclamation-triangle', 'class' => 'bg-red-50 text-red-600 dark:bg-red-900/30'],
        'warning' => ['icon' => 'exclamation-circle', 'class' => 'bg-amber-50 text-amber-600 dark:bg-amber-900/30'],
        'info' => ['icon' => 'information-circle', 'class' => 'bg-sky-50 text-sky-600 dark:bg-sky-900/30'],
    ];

    $tone = $tones[$variant] ?? $tones['danger'];
@endphp

<flux:modal name="confirm-action" class="max-w-md" wire:close="cancelPendingAction">
    <div class="flex items-start gap-4">
        <span class="shrink-0 rounded-full p-3 {{ $tone['class'] }}">
            <flux:icon :name="$tone['icon']" class="size-6" />
        </span>

        <div class="min-w-0 flex-1">
            <flux:heading size="lg">{{ $pending['heading'] ?? 'Are you sure?' }}</flux:heading>

            <flux:text class="mt-2">
                {{ $pending['text'] ?? 'This action cannot be undone.' }}
            </flux:text>
        </div>
    </div>

    <div class="mt-6 flex justify-end gap-3">
        <flux:modal.close>
            <flux:button variant="ghost">Cancel</flux:button>
        </flux:modal.close>

        <flux:button
            wire:click="runPendingAction"
            :variant="$variant === 'danger' ? 'danger' : 'primary'"
        >
            <span wire:loading.remove wire:target="runPendingAction">
                {{ $pending['confirm'] ?? 'Confirm' }}
            </span>
            <span wire:loading wire:target="runPendingAction">Working…</span>
        </flux:button>
    </div>
</flux:modal>
