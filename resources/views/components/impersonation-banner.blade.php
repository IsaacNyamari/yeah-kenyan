@php
    $impersonator = app(\App\Services\Impersonator::class);
    $original = $impersonator->impersonator();
@endphp

@if ($original)
    {{-- Sticky rather than fixed: it stays in view while scrolling but still
         occupies layout space, so it pushes the page down instead of sitting
         on top of it. Acting as someone else without realising is how an
         administrator does damage by accident, so it is deliberately loud. --}}
    <div class="sticky top-0 z-90 border-b-2 border-amber-500 bg-amber-100 px-4 py-2.5 text-amber-950 dark:bg-amber-900 dark:text-amber-50">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3">
            <p class="text-sm">
                Viewing as <strong>{{ auth()->user()?->name }}</strong>@if (auth()->user()?->primaryRole()), {{ auth()->user()->primaryRole()->label() }}@endif.
                You are signed in as <strong>{{ $original->name }}</strong>.
            </p>

            <form method="POST" action="{{ route('impersonate.stop') }}">
                @csrf
                <button type="submit"
                        class="rounded-lg bg-amber-950 px-3 py-1.5 text-sm font-semibold text-amber-50 transition hover:bg-amber-800 dark:bg-amber-50 dark:text-amber-950 dark:hover:bg-white">
                    Back to my account
                </button>
            </form>
        </div>
    </div>
@endif
