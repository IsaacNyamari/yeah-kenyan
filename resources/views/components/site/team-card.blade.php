@props(['member'])

<div class="rounded-xl border border-zinc-200 bg-white p-6 text-center text-zinc-900 transition duration-300 hover:-translate-y-2 hover:shadow-xl dark:border-zinc-800 dark:bg-zinc-900 dark:text-zinc-100">
    <img src="{{ asset($member->photo) }}" alt="{{ $member->name }}" loading="lazy"
         class="mx-auto size-28 rounded-full object-cover ring-4 ring-zinc-100 dark:ring-zinc-800">

    <h3 class="mt-4 font-bold">{{ $member->name }}</h3>

    <p class="text-sm font-medium text-zinc-500">{{ $member->role }}</p>

    <p class="mt-3 text-xs leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $member->bio }}</p>
</div>
