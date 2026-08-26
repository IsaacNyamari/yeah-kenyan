<?php

use App\Models\Setting;
use Flux\Flux;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Contact Settings')] class extends Component {
    public string $contact_heading = '';

    public string $contact_intro = '';

    public string $contact_button_label = '';

    public string $contact_success_message = '';

    public string $contact_address = '';

    public string $contact_email = '';

    public string $contact_phone = '';

    public string $social_facebook = '';

    public string $social_instagram = '';

    public string $social_youtube = '';

    public bool $tawk_enabled = false;

    public string $tawk_property_id = '';

    public string $tawk_widget_id = '';

    /**
     * Keys managed by this screen, in the order they are stored.
     *
     * @var list<string>
     */
    private const KEYS = [
        'contact_heading', 'contact_intro', 'contact_button_label', 'contact_success_message',
        'contact_address', 'contact_email', 'contact_phone',
        'social_facebook', 'social_instagram', 'social_youtube',
        'tawk_property_id', 'tawk_widget_id',
    ];

    public function mount(): void
    {
        foreach (self::KEYS as $key) {
            $this->{$key} = (string) Setting::get($key, '');
        }

        $this->tawk_enabled = Setting::get('tawk_enabled') === '1';
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'contact_heading' => ['required', 'string', 'max:160'],
            'contact_intro' => ['nullable', 'string', 'max:500'],
            'contact_button_label' => ['required', 'string', 'max:60'],
            'contact_success_message' => ['required', 'string', 'max:300'],
            'contact_address' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:60'],
            'social_facebook' => ['nullable', 'url', 'max:255'],
            'social_instagram' => ['nullable', 'url', 'max:255'],
            'social_youtube' => ['nullable', 'url', 'max:255'],
            'tawk_property_id' => ['nullable', 'string', 'alpha_num', 'max:64', 'required_if:tawk_enabled,true'],
            'tawk_widget_id' => ['nullable', 'string', 'alpha_num', 'max:64', 'required_if:tawk_enabled,true'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Setting::putMany([...$validated, 'tawk_enabled' => $this->tawk_enabled ? '1' : '0']);

        Flux::toast(variant: 'success', heading: 'Saved', text: 'Contact settings updated.');
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Contact Settings</flux:heading>
        <flux:text class="mt-1">Edit the contact page copy and details. The preview updates as you type.</flux:text>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">

        {{-- Form --}}
        <flux:card>
            <form wire:submit="save" class="space-y-5">
                <flux:heading size="lg">Page copy</flux:heading>

                <flux:input wire:model.live.debounce.300ms="contact_heading" label="Page heading" required />
                <flux:textarea wire:model.live.debounce.300ms="contact_intro" label="Intro text" rows="2" />
                <flux:input wire:model.live.debounce.300ms="contact_button_label" label="Submit button label" required />
                <flux:textarea wire:model="contact_success_message" label="Success message"
                               description="Shown in the toast after a visitor submits the form" rows="2" required />

                <flux:separator />

                <flux:heading size="lg">Contact details</flux:heading>

                <flux:input wire:model.live.debounce.300ms="contact_address" label="Office address" required />
                <flux:input wire:model.live.debounce.300ms="contact_email" type="email" label="Email address" required />
                <flux:input wire:model.live.debounce.300ms="contact_phone" label="Phone number" required />

                <flux:separator />

                <flux:heading size="lg">Social links</flux:heading>

                <flux:input wire:model="social_facebook" label="Facebook URL" placeholder="https://…" />
                <flux:input wire:model="social_instagram" label="Instagram URL" placeholder="https://…" />
                <flux:input wire:model="social_youtube" label="YouTube URL" placeholder="https://…" />

                <flux:separator />

                <flux:heading size="lg">Live chat</flux:heading>

                <flux:checkbox wire:model.live="tawk_enabled" label="Show the Tawk.to chat widget"
                               description="Appears on the public site only, never in this admin area" />

                @if ($tawk_enabled)
                    <flux:input wire:model="tawk_property_id" label="Tawk property ID" required />
                    <flux:input wire:model="tawk_widget_id" label="Tawk widget ID" required>
                        <x-slot:description>
                            Both come from the Tawk dashboard widget URL:
                            <span class="font-mono">embed.tawk.to/&lt;property&gt;/&lt;widget&gt;</span>
                        </x-slot:description>
                    </flux:input>
                @endif

                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="save">Save changes</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </flux:button>
            </form>
        </flux:card>

        {{-- Live preview --}}
        <div class="xl:sticky xl:top-6 xl:self-start">
            <div class="mb-2 flex items-center gap-2">
                <flux:icon.eye class="size-4 text-zinc-500" />
                <flux:text size="sm">Live preview</flux:text>
            </div>

            {{--
                The preview renders the very same component the public page does,
                scaled down. Rendering it at desktop width and shrinking it keeps
                the real breakpoints intact, so this is a true miniature rather
                than a lookalike that can drift out of sync.
            --}}
            <div class="relative h-[680px] overflow-hidden rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-950">
                <div class="pointer-events-none absolute top-0 left-0 origin-top-left"
                     style="width: 1280px; transform: scale(0.53);">
                    <x-site.contact-body
                        preview
                        :heading="$contact_heading ?: 'Contact Us For Any Queries'"
                        :intro="$contact_intro"
                        :address="$contact_address"
                        :email="$contact_email"
                        :phone="$contact_phone"
                        :facebook="$social_facebook"
                        :instagram="$social_instagram"
                        :youtube="$social_youtube"
                        :button-label="$contact_button_label"
                    />
                </div>
            </div>

            <flux:text size="sm" class="mt-3">
                The success toast will read: &ldquo;{{ $contact_success_message ?: '—' }}&rdquo;
            </flux:text>
        </div>
    </div>
</div>
