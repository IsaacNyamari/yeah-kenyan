<?php

use App\Models\Setting;
use App\Services\NewYorkTimesFeed;
use Flux\Flux;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Layout('layouts.app')] #[Title('Settings')] class extends Component {
    #[Url]
    public string $tab = 'general';

    // General
    public string $site_name = '';

    public string $site_slogan = '';

    public string $meta_description = '';

    public string $meta_keywords = '';

    public string $site_timezone = 'Africa/Nairobi';

    // Mail
    public string $mail_host = '';

    public string $mail_port = '587';

    public string $mail_username = '';

    public string $mail_password = '';

    public string $mail_encryption = 'tls';

    public string $mail_from_address = '';

    public string $mail_from_name = '';

    public string $mail_enquiries_to = '';

    // Analytics
    public string $analytics_property_id = '';

    public string $analytics_credentials = '';

    public bool $analytics_tracking_enabled = false;

    public string $analytics_measurement_id = '';

    // Integrations
    public bool $tawk_enabled = false;

    public string $tawk_property_id = '';

    public string $tawk_widget_id = '';

    // New York Times wire feed
    public bool $nyt_enabled = false;

    public string $nyt_api_key = '';

    public string $nyt_section = 'home';

    public string $nyt_limit = '6';

    /**
     * Plain-text keys, grouped by the tab that owns them.
     *
     * @var array<string, list<string>>
     */
    private const GROUPS = [
        'general' => ['site_name', 'site_slogan', 'site_timezone', 'meta_description', 'meta_keywords'],
        'mail' => ['mail_host', 'mail_port', 'mail_username', 'mail_encryption', 'mail_from_address', 'mail_from_name', 'mail_enquiries_to'],
        'analytics' => ['analytics_property_id', 'analytics_measurement_id'],
        'integrations' => ['tawk_property_id', 'tawk_widget_id', 'nyt_section', 'nyt_limit'],
    ];

    public function mount(): void
    {
        foreach (self::GROUPS as $keys) {
            foreach ($keys as $key) {
                $this->{$key} = (string) Setting::get($key, $this->{$key});
            }
        }

        $this->tawk_enabled = Setting::boolean('tawk_enabled');
        $this->nyt_enabled = Setting::boolean('nyt_enabled');
        $this->analytics_tracking_enabled = Setting::boolean('analytics_tracking_enabled');

        // Secrets are never echoed back into the form. A blank field means
        // "leave whatever is stored alone".
        $this->mail_password = '';
        $this->analytics_credentials = '';
        $this->nyt_api_key = '';
    }

    /**
     * Every IANA zone, grouped by region and labelled with its current offset.
     *
     * @return array<string, array<string, string>>
     */
    #[\Livewire\Attributes\Computed]
    public function timezones(): array
    {
        $grouped = [];

        foreach (\DateTimeZone::listIdentifiers() as $zone) {
            $region = str_contains($zone, '/') ? strtok($zone, '/') : 'Other';
            $offset = (new \DateTimeZone($zone))->getOffset(new \DateTime('now', new \DateTimeZone('UTC')));

            $sign = $offset < 0 ? '-' : '+';
            $offset = abs($offset);

            $grouped[$region][$zone] = sprintf(
                '%s (UTC%s%02d:%02d)',
                str_replace(['_', '/'], [' ', ' / '], $zone),
                $sign,
                intdiv($offset, 3600),
                intdiv($offset % 3600, 60),
            );
        }

        // Kenya first, since that is where this business operates.
        return ['Africa' => $grouped['Africa'] ?? []] + $grouped;
    }

    /**
     * The clock in the selected zone, or a placeholder if it is not a real one.
     * Rendering must not throw while the field is mid-edit.
     */
    #[\Livewire\Attributes\Computed]
    public function currentTimeInZone(): string
    {
        try {
            return now()->setTimezone($this->site_timezone ?: 'UTC')->format('D, M j Y H:i');
        } catch (\Throwable) {
            return 'not a valid timezone';
        }
    }

    #[\Livewire\Attributes\Computed]
    public function hasStoredMailPassword(): bool
    {
        return filled(Setting::get('mail_password'));
    }

    #[\Livewire\Attributes\Computed]
    public function hasStoredNewsKey(): bool
    {
        return filled(Setting::get('nyt_api_key'));
    }

    #[\Livewire\Attributes\Computed]
    public function hasStoredCredentials(): bool
    {
        return filled(Setting::get('analytics_credentials'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'site_name' => ['required', 'string', 'max:160'],
            'site_slogan' => ['nullable', 'string', 'max:160'],
            'site_timezone' => ['required', 'timezone'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'meta_keywords' => ['nullable', 'string', 'max:1000'],

            'mail_host' => ['nullable', 'string', 'max:255'],
            'mail_port' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'mail_username' => ['nullable', 'string', 'max:255'],
            'mail_password' => ['nullable', 'string', 'max:255'],
            'mail_encryption' => ['required', 'in:tls,ssl,none'],
            'mail_from_address' => ['nullable', 'email', 'max:255'],
            'mail_from_name' => ['nullable', 'string', 'max:160'],
            'mail_enquiries_to' => ['nullable', 'email', 'max:255'],

            'analytics_property_id' => ['nullable', 'string', 'regex:/^\d*$/', 'max:32'],
            'analytics_measurement_id' => ['nullable', 'string', 'regex:/^G-[A-Z0-9]+$/', 'max:32'],
            'analytics_credentials' => ['nullable', 'string', 'json'],

            'tawk_property_id' => ['nullable', 'string', 'alpha_num', 'max:64', 'required_if:tawk_enabled,true'],
            'tawk_widget_id' => ['nullable', 'string', 'alpha_num', 'max:64', 'required_if:tawk_enabled,true'],

            'nyt_api_key' => ['nullable', 'string', 'max:120'],
            'nyt_section' => ['required', 'string', 'in:'.implode(',', NewYorkTimesFeed::SECTIONS)],
            'nyt_limit' => ['required', 'integer', 'min:1', 'max:20'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            'analytics_property_id.regex' => 'The GA4 property ID is numeric only, e.g. 123456789.',
            'analytics_credentials.json' => 'Paste the service-account key exactly as downloaded — it must be valid JSON.',
            'analytics_measurement_id.regex' => 'A measurement ID looks like G-XXXXXXXXXX.',
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $values = collect($validated)
            // Blank secret fields mean "keep the stored value".
            ->reject(fn ($value, $key): bool => in_array($key, Setting::SECRETS, true) && blank($value))
            ->all();

        $values['tawk_enabled'] = $this->tawk_enabled;
        $values['nyt_enabled'] = $this->nyt_enabled;
        $values['analytics_tracking_enabled'] = $this->analytics_tracking_enabled;

        Setting::putMany($values);

        $this->mail_password = '';
        $this->analytics_credentials = '';
        $this->nyt_api_key = '';

        // Section or key may have changed, so drop the cached headlines.
        app(NewYorkTimesFeed::class)->flush();

        Flux::toast(variant: 'success', heading: 'Saved', text: 'Settings updated.');
    }

    /**
     * Send a real message through the saved settings to prove they work.
     */
    public function sendTestEmail(): void
    {
        $recipient = auth()->user()->email;

        try {
            Mail::raw(
                "This is a test message from {$this->site_name}.\n\nIf you are reading it, the mail settings are working.",
                fn ($message) => $message->to($recipient)->subject('Mail settings test'),
            );

            Flux::toast(variant: 'success', heading: 'Test sent', text: "Check {$recipient}.");
        } catch (\Throwable $e) {
            Flux::toast(
                variant: 'danger',
                heading: 'Could not send',
                text: str($e->getMessage())->limit(160)->toString(),
            );
        }
    }

    /**
     * Verify the wire feed credentials without saving them.
     */
    public function testNewsFeed(): void
    {
        $key = $this->nyt_api_key ?: (string) Setting::get('nyt_api_key');

        if (blank($key)) {
            Flux::toast(variant: 'warning', text: 'Enter an API key first.');

            return;
        }

        $result = app(NewYorkTimesFeed::class)->check($key, $this->nyt_section);

        Flux::toast(
            variant: $result['ok'] ? 'success' : 'danger',
            heading: $result['ok'] ? 'Connected' : 'Could not connect',
            text: $result['message'],
        );
    }

    public function clearAnalyticsCredentials(): void
    {
        Setting::putMany(['analytics_credentials' => null]);

        Flux::toast(variant: 'success', text: 'Service-account key removed.');
    }
}; ?>

<div class="space-y-6">
    <div>
        <flux:heading size="xl">Settings</flux:heading>
        <flux:text class="mt-1">
            Site details, mail, analytics and integrations — all editable here, no server access needed.
        </flux:text>
    </div>

    <div>
        <div class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-700" role="tablist">
            @foreach (['general' => 'cog-6-tooth', 'mail' => 'envelope', 'analytics' => 'chart-bar', 'integrations' => 'puzzle-piece'] as $name => $icon)
                <button type="button" wire:click="$set('tab', '{{ $name }}')"
                        role="tab" @aria-selected="$tab === $name ? 'true' : 'false'"
                        @class([
                            'flex items-center gap-2 border-b-2 px-4 py-3 text-sm font-medium capitalize transition',
                            'border-brand-600 text-brand-600' => $tab === $name,
                            'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-800 dark:hover:text-zinc-200' => $tab !== $name,
                        ])>
                    <flux:icon :name="$icon" class="size-4" />
                    {{ $name === 'general' ? 'General' : ucfirst($name) }}
                </button>
            @endforeach
        </div>

        <form wire:submit="save">
            {{-- General --}}
            <div class="mt-6" @if($tab !== 'general') hidden @endif>
                <flux:card class="space-y-5">
                    <flux:heading size="lg">Site details</flux:heading>

                    <flux:input wire:model="site_name" label="Site name" required />
                    <flux:input wire:model="site_slogan" label="Slogan" />

                    <flux:select wire:model="site_timezone" label="Timezone"
                                 description="Dates across the site and dashboard are shown in this zone">
                        @foreach ($this->timezones as $region => $zones)
                            <flux:select.group :label="$region">
                                @foreach ($zones as $zone => $label)
                                    <flux:select.option value="{{ $zone }}">{{ $label }}</flux:select.option>
                                @endforeach
                            </flux:select.group>
                        @endforeach
                    </flux:select>

                    <flux:callout variant="secondary">
                        <flux:callout.text>
                            Timestamps are stored in UTC and converted for display, so changing this never alters
                            records that already exist. Right now that reads
                            <strong>{{ $this->currentTimeInZone }}</strong>.
                        </flux:callout.text>
                    </flux:callout>
                    <flux:textarea wire:model="meta_description" label="Meta description" rows="3"
                                   description="Shown by search engines beneath your site name" />
                    <flux:textarea wire:model="meta_keywords" label="Meta keywords" rows="2"
                                   description="Comma separated" />
                </flux:card>
            </div>

            {{-- Mail --}}
            <div class="mt-6" @if($tab !== 'mail') hidden @endif>
                <flux:card class="space-y-5">
                    <flux:heading size="lg">Mail server</flux:heading>
                    <flux:text size="sm">
                        Used for contact-form alerts, acknowledgements, and password resets.
                    </flux:text>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="mail_host" label="Server name" placeholder="mail.example.com" />
                        <flux:input wire:model="mail_port" type="number" label="Port" placeholder="587" />
                    </div>

                    <flux:input wire:model="mail_username" label="User name" placeholder="you@example.com" />

                    <flux:input wire:model="mail_password" type="password" label="Password"
                                :placeholder="$this->hasStoredMailPassword ? 'Stored — leave blank to keep' : 'Enter the mailbox password'"
                                viewable />

                    <flux:select wire:model="mail_encryption" label="Encryption">
                        <flux:select.option value="tls">STARTTLS (usually port 587)</flux:select.option>
                        <flux:select.option value="ssl">SSL/TLS (usually port 465)</flux:select.option>
                        <flux:select.option value="none">None</flux:select.option>
                    </flux:select>

                    <flux:separator />

                    <flux:heading size="lg">Addresses</flux:heading>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <flux:input wire:model="mail_from_address" type="email" label="Send from"
                                    description="Must be a domain this server may send for" />
                        <flux:input wire:model="mail_from_name" label="Send as" placeholder="Yeah Kenyan Events Limited" />
                    </div>

                    <flux:input wire:model="mail_enquiries_to" type="email" label="Deliver enquiries to"
                                description="Where contact-form submissions are emailed" />

                    <flux:callout variant="secondary">
                        <flux:callout.heading>Test before relying on it</flux:callout.heading>
                        <flux:callout.text>
                            Save first, then send a test message to your own address
                            ({{ auth()->user()->email }}) to confirm the settings work.
                        </flux:callout.text>
                    </flux:callout>

                    <flux:button type="button" variant="filled" icon="paper-airplane" wire:click="sendTestEmail">
                        <span wire:loading.remove wire:target="sendTestEmail">Send test email</span>
                        <span wire:loading wire:target="sendTestEmail">Sending…</span>
                    </flux:button>
                </flux:card>
            </div>

            {{-- Analytics --}}
            <div class="mt-6" @if($tab !== 'analytics') hidden @endif>
                <flux:card class="space-y-5">
                    <flux:heading size="lg">Tracking</flux:heading>
                    <flux:text size="sm">
                        Adds the Google tag to the public site so visits are recorded. Without it there is no data
                        for the dashboard below to report on.
                    </flux:text>

                    <flux:checkbox wire:model.live="analytics_tracking_enabled" label="Record visits with Google Analytics"
                                   description="Never loaded in the admin area, or on local and test environments" />

                    @if ($analytics_tracking_enabled)
                        <flux:input wire:model="analytics_measurement_id" label="Measurement ID" placeholder="G-XXXXXXXXXX"
                                    description="Google Analytics → Admin → Data Streams → your web stream" />
                    @endif
                </flux:card>

                <flux:card class="mt-6 space-y-5">
                    <flux:heading size="lg">Dashboard reporting</flux:heading>
                    <flux:text size="sm">
                        Separate from tracking: these credentials let the dashboard read your traffic back out of
                        Google. The property ID is the numeric one, not the G- measurement ID above.
                    </flux:text>

                    <flux:input wire:model="analytics_property_id" label="GA4 property ID" placeholder="123456789"
                                description="Google Analytics → Admin → Property Settings" />

                    <flux:textarea wire:model="analytics_credentials" rows="8"
                                   label="Service-account key (JSON)"
                                   :placeholder="$this->hasStoredCredentials ? 'Stored — paste a new key to replace it' : '{ &quot;type&quot;: &quot;service_account&quot;, ... }'" />

                    <flux:text size="sm">
                        Create a service account in Google Cloud, enable the Google Analytics Data API, grant that
                        account Viewer access on the property, then paste its JSON key here. Stored encrypted.
                    </flux:text>

                    @if ($this->hasStoredCredentials)
                        <div class="flex items-center gap-3">
                            <flux:badge color="lime" icon="check-circle">Key stored</flux:badge>
                            <flux:button type="button" size="sm" variant="ghost" wire:click="clearAnalyticsCredentials">
                                Remove key
                            </flux:button>
                        </div>
                    @endif

                    <flux:callout variant="secondary">
                        <flux:callout.text>
                            Held in the database rather than as a file on disk, so it survives deployment and needs
                            no writable path on shared hosting.
                        </flux:callout.text>
                    </flux:callout>
                </flux:card>
            </div>

            {{-- Integrations --}}
            <div class="mt-6" @if($tab !== 'integrations') hidden @endif>
                <flux:card class="space-y-5">
                    <flux:heading size="lg">Live chat</flux:heading>

                    <flux:checkbox wire:model.live="tawk_enabled" label="Show the Tawk.to chat widget"
                                   description="Appears on the public site only, never in this admin area" />

                    @if ($tawk_enabled)
                        <div class="grid gap-5 sm:grid-cols-2">
                            <flux:input wire:model="tawk_property_id" label="Property ID" required />
                            <flux:input wire:model="tawk_widget_id" label="Widget ID" required />
                        </div>

                        <flux:text size="sm">
                            Both appear in your Tawk dashboard widget URL:
                            <span class="font-mono">embed.tawk.to/&lt;property&gt;/&lt;widget&gt;</span>
                        </flux:text>
                    @endif

                    <flux:separator />

                    <flux:heading size="lg">New York Times headlines</flux:heading>

                    <flux:checkbox wire:model.live="nyt_enabled" label="Show New York Times headlines alongside our news"
                                   description="Fetched live and never stored — each headline links back to nytimes.com" />

                    @if ($nyt_enabled)
                        <flux:input wire:model="nyt_api_key" type="password" label="API key"
                                    :placeholder="$this->hasStoredNewsKey ? 'Stored — leave blank to keep' : 'Your NYT developer API key'"
                                    viewable />

                        <div class="grid gap-5 sm:grid-cols-2">
                            <flux:select wire:model="nyt_section" label="Section">
                                @foreach (\App\Services\NewYorkTimesFeed::SECTIONS as $section)
                                    <flux:select.option value="{{ $section }}">{{ ucfirst(str_replace('-', ' ', $section)) }}</flux:select.option>
                                @endforeach
                            </flux:select>

                            <flux:input wire:model="nyt_limit" type="number" label="Headlines to show" min="1" max="20" />
                        </div>

                        <flux:button type="button" variant="filled" icon="signal" wire:click="testNewsFeed">
                            <span wire:loading.remove wire:target="testNewsFeed">Test connection</span>
                            <span wire:loading wire:target="testNewsFeed">Checking…</span>
                        </flux:button>

                        <flux:callout variant="secondary">
                            <flux:callout.text>
                                Only the headline, the summary the API supplies, and a link back are shown, each
                                credited to The New York Times. Responses are cached for 15 minutes to stay inside
                                the API rate limits.
                            </flux:callout.text>
                        </flux:callout>
                    @endif
                </flux:card>
            </div>

            <div class="mt-6">
                <flux:button type="submit" variant="primary">
                    <span wire:loading.remove wire:target="save">Save settings</span>
                    <span wire:loading wire:target="save">Saving…</span>
                </flux:button>
            </div>
        </form>
    </div>
</div>
