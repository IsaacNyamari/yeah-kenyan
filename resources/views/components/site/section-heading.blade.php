@props(['title'])

<div {{ $attributes->merge(['class' => 'mb-6 border-b-2 border-zinc-200 dark:border-zinc-800']) }}>
    <h2 class="-mb-0.5 inline-block border-b-2 border-brand-600 pb-2 text-lg font-bold tracking-wide uppercase">
        {{ $title }}
    </h2>
</div>
