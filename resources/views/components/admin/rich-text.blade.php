@props([
    'model',
    'label' => null,
    'description' => null,
    // Screens with a preview beside the editor need the server to see each
    // change; everywhere else defers until save.
    'live' => false,
])

@php
    $buttons = [
        ['command' => 'bold', 'key' => 'bold', 'label' => 'Bold', 'icon' => 'bold'],
        ['command' => 'italic', 'key' => 'italic', 'label' => 'Italic', 'icon' => 'italic'],
        ['command' => 'underline', 'key' => 'underline', 'label' => 'Underline', 'icon' => 'underline'],
        ['command' => 'h3', 'key' => 'h3', 'label' => 'Heading', 'text' => 'H3'],
        ['command' => 'h4', 'key' => 'h4', 'label' => 'Subheading', 'text' => 'H4'],
        ['command' => 'bulletList', 'key' => 'bulletList', 'label' => 'Bulleted list', 'icon' => 'list-bullet'],
        ['command' => 'orderedList', 'key' => 'orderedList', 'label' => 'Numbered list', 'icon' => 'numbered-list'],
        ['command' => 'blockquote', 'key' => 'blockquote', 'label' => 'Quote', 'icon' => 'chat-bubble-bottom-center-text'],
    ];
@endphp

<div>
    @if ($label)
        <flux:label>{{ $label }}</flux:label>
    @endif

    {{--
        wire:ignore is essential: the editor owns this DOM, and letting Livewire
        morph it on every round trip would wipe the caret mid-sentence.
    --}}
    <div wire:ignore x-data="richText('{{ $model }}', {{ $live ? 'true' : 'false' }})" x-on:destroy="destroy()"
         class="mt-1 overflow-hidden rounded-lg border border-zinc-200 focus-within:border-brand-500 dark:border-zinc-700">

        <div class="flex flex-wrap items-center gap-0.5 border-b border-zinc-200 bg-zinc-50 p-1.5 dark:border-zinc-700 dark:bg-zinc-800">
            @foreach ($buttons as $button)
                <button type="button" x-on:click="run('{{ $button['command'] }}')"
                        :class="active.{{ $button['key'] }}
                            ? 'bg-brand-600 text-white'
                            : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700'"
                        class="flex size-8 items-center justify-center rounded text-sm font-semibold transition"
                        title="{{ $button['label'] }}" aria-label="{{ $button['label'] }}">
                    @isset($button['icon'])
                        <flux:icon :name="$button['icon']" class="size-4" />
                    @else
                        {{ $button['text'] }}
                    @endisset
                </button>
            @endforeach

            <button type="button" x-on:click="toggleLink()"
                    :class="active.link
                        ? 'bg-brand-600 text-white'
                        : 'text-zinc-600 hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700'"
                    class="flex size-8 items-center justify-center rounded transition"
                    title="Link" aria-label="Link">
                <flux:icon name="link" class="size-4" />
            </button>

            <div class="mx-1 h-5 w-px bg-zinc-300 dark:bg-zinc-600"></div>

            @foreach ([['undo', 'Undo', 'arrow-uturn-left'], ['redo', 'Redo', 'arrow-uturn-right'], ['clear', 'Clear formatting', 'x-mark']] as [$command, $label, $icon])
                <button type="button" x-on:click="run('{{ $command }}')"
                        class="flex size-8 items-center justify-center rounded text-zinc-600 transition hover:bg-zinc-200 dark:text-zinc-300 dark:hover:bg-zinc-700"
                        title="{{ $label }}" aria-label="{{ $label }}">
                    <flux:icon :name="$icon" class="size-4" />
                </button>
            @endforeach
        </div>

        <div x-ref="editor" class="bg-white dark:bg-zinc-900"></div>
    </div>

    @if ($description)
        <flux:description class="mt-1">{{ $description }}</flux:description>
    @endif

    {{-- Outside wire:ignore, so a validation error still reaches the page. --}}
    <flux:error :name="$model" />
</div>
