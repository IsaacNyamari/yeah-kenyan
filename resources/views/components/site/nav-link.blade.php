@props(['href', 'active' => false])

<a wire:navigate href="{{ $href }}"
   @class([
       'px-4 py-4 text-sm font-medium transition',
       'text-brand-400' => $active,
       'hover:text-brand-400' => ! $active,
   ])
   @if ($active) aria-current="page" @endif
>
    {{ $slot }}
</a>
