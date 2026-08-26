@props([
    'heading',
    'intro' => null,
    'address' => null,
    'email' => null,
    'phone' => null,
    'facebook' => null,
    'instagram' => null,
    'youtube' => null,
    'buttonLabel' => 'Send Message',
    // Preview mode renders identical markup with an inert form, so the admin
    // screen and the public page can never drift apart visually.
    'preview' => false,
])

<div>
    <section class="bg-zinc-900 py-20">
        <div class="mx-auto max-w-7xl px-6">
            <span class="inline-block bg-brand-600 px-3 py-1 text-xs font-semibold tracking-wider text-white uppercase">
                Get In Touch
            </span>
            <h1 class="mt-4 text-4xl font-bold tracking-tight text-white uppercase sm:text-5xl">
                {{ $heading }}
            </h1>
            @if (filled($intro))
                <p class="mt-4 max-w-3xl text-lg text-zinc-400">{{ $intro }}</p>
            @endif
        </div>
    </section>

    <div class="mx-auto max-w-7xl px-6 py-16">
        <div class="grid gap-12 lg:grid-cols-3">

            {{-- Contact details --}}
            <div class="space-y-6">
                <x-site.section-heading title="Contact Info" />

                <div class="flex items-start gap-4 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
                    <div class="rounded-full bg-brand-50 p-3 dark:bg-brand-900/30">
                        <flux:icon.map-pin class="size-5 text-brand-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Our Office</h3>
                        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">{{ $address ?: '—' }}</p>
                    </div>
                </div>

                <div class="flex items-start gap-4 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
                    <div class="rounded-full bg-brand-50 p-3 dark:bg-brand-900/30">
                        <flux:icon.envelope class="size-5 text-brand-600" />
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-semibold">Email Us</h3>
                        <a href="mailto:{{ $email }}"
                           class="mt-1 block truncate text-sm text-zinc-600 transition hover:text-brand-600 dark:text-zinc-400">
                            {{ $email ?: '—' }}
                        </a>
                    </div>
                </div>

                <div class="flex items-start gap-4 rounded-lg border border-zinc-200 p-5 dark:border-zinc-800">
                    <div class="rounded-full bg-brand-50 p-3 dark:bg-brand-900/30">
                        <flux:icon.phone class="size-5 text-brand-600" />
                    </div>
                    <div>
                        <h3 class="font-semibold">Call Us</h3>
                        <a href="tel:{{ str_replace(' ', '', (string) $phone) }}"
                           class="mt-1 block text-sm text-zinc-600 transition hover:text-brand-600 dark:text-zinc-400">
                            {{ $phone ?: '—' }}
                        </a>
                    </div>
                </div>

                <div class="rounded-lg bg-zinc-900 p-6 text-white">
                    <h3 class="font-bold uppercase">Follow Us</h3>
                    <div class="mt-4 flex gap-3">
                        @foreach (['facebook' => $facebook, 'instagram' => $instagram, 'youtube' => $youtube] as $network => $url)
                            <a @if (filled($url)) href="{{ $url }}" target="_blank" rel="noopener" @endif
                               aria-label="{{ ucfirst($network) }}"
                               @class([
                                   'rounded-full p-2 transition',
                                   'bg-zinc-800 hover:bg-brand-600' => filled($url),
                                   'bg-zinc-800/50 text-zinc-600' => blank($url),
                               ])>
                                <x-site.social-icon :name="$network" class="size-4" />
                            </a>
                        @endforeach
                    </div>
                    <p class="mt-3 text-sm text-zinc-400">&#64;yeahkenyan</p>
                </div>
            </div>

            {{-- Form --}}
            <div class="lg:col-span-2">
                <x-site.section-heading title="Send Us A Message" />

                @if ($preview)
                    {{-- Same fields, no bindings: this is a picture of the form, not the form. --}}
                    <div class="space-y-6" aria-hidden="true">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <flux:input label="Your Name" placeholder="Jane Wanjiru" readonly />
                            <flux:input type="email" label="Your Email" placeholder="jane@example.com" readonly />
                        </div>

                        <flux:input label="Subject" placeholder="Event enquiry" readonly />

                        <flux:textarea
                            label="Message"
                            placeholder="Tell us about your event, the date, and what you need from us."
                            rows="8"
                            readonly
                        />

                        <flux:button variant="primary">{{ $buttonLabel ?: 'Send Message' }}</flux:button>
                    </div>
                @else
                    <form wire:submit="send" class="space-y-6">
                        <div class="grid gap-6 sm:grid-cols-2">
                            <flux:input wire:model="name" label="Your Name" placeholder="Jane Wanjiru" required />
                            <flux:input wire:model="email" type="email" label="Your Email" placeholder="jane@example.com" required />
                        </div>

                        <flux:input wire:model="subject" label="Subject" placeholder="Event enquiry" required />

                        <flux:textarea
                            wire:model="message"
                            label="Message"
                            placeholder="Tell us about your event, the date, and what you need from us."
                            rows="8"
                            required
                        />

                        <flux:button type="submit" variant="primary">
                            <span wire:loading.remove wire:target="send">{{ $buttonLabel ?: 'Send Message' }}</span>
                            <span wire:loading wire:target="send">Sending...</span>
                        </flux:button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</div>
