<?php

use App\Models\Setting;
use App\Services\GitDeployer;
use App\Services\NewYorkTimesFeed;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;
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

    // Deployment
    /** @var array<string, mixed> */
    public array $deployStatus = [];

    /** @var array<int, array{key: string, label: string, state: string, output: string}> */
    public array $deploySteps = [];

    public bool $deploying = false;

    public int $deployStep = 0;

    public string $deployBlocked = '';

    public string $deployHint = '';

    public string $deployError = '';

    public string $deployRemote = '';

    /**
     * The order a deploy runs in. Each is a separate request so the browser can
     * show which one is happening rather than one silent wait.
     */
    private const DEPLOY_STEPS = [
        ['key' => 'pull', 'label' => 'Pulling the latest code'],
        ['key' => 'migrate', 'label' => 'Running database migrations'],
        ['key' => 'cache', 'label' => 'Rebuilding caches'],
    ];

    // Access
    public bool $registration_enabled = true;

    public bool $posting_enabled = true;

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

        $this->registration_enabled = Setting::boolean('registration_enabled', true);
        $this->posting_enabled = Setting::boolean('posting_enabled', true);
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

    #[\Livewire\Attributes\Computed]
    public function installedVersion(): string
    {
        return app(\App\Services\AppVersion::class)->current();
    }

    /**
     * Whether the remote has published a version newer than the one running.
     */
    #[\Livewire\Attributes\Computed]
    public function releaseAvailable(): bool
    {
        $latest = $this->deployStatus['latestVersion'] ?? null;

        return is_string($latest) && app(\App\Services\AppVersion::class)->isBehind($latest);
    }

    /**
     * Ask the remote what is waiting.
     */
    public function checkForUpdates(GitDeployer $deployer): void
    {
        $this->authorizeDeploy();

        $support = $deployer->support();

        if (! $support['ok']) {
            $this->deployBlocked = (string) $support['reason'];
            $this->deployStatus = [];

            Flux::toast(variant: 'danger', heading: 'Cannot deploy from here', text: $this->deployBlocked);

            return;
        }

        $this->deployBlocked = '';
        $this->deployStatus = $deployer->status();

        $this->deployRemote = $deployer->remote();

        if (! $this->deployStatus['ok']) {
            $this->deployHint = (string) $deployer->diagnose($this->deployStatus['error']);
            // Shown in full alongside the hint: the hint is an interpretation,
            // and the reader needs the evidence when it guesses wrong.
            $this->deployError = str((string) $this->deployStatus['error'])->limit(1500)->toString();

            Flux::toast(
                variant: 'danger',
                heading: 'Could not reach the repository',
                text: str((string) $this->deployStatus['error'])->limit(200)->toString(),
            );

            return;
        }

        $this->deployHint = '';
        $this->deployError = '';

        if ($this->deployStatus['upToDate']) {
            Flux::toast(
                variant: 'success',
                heading: 'Already up to date',
                text: 'This site is running the latest code on '.$this->deployStatus['branch'].'.',
            );

            return;
        }

        Flux::toast(
            variant: 'warning',
            heading: 'Updates available',
            text: $this->deployStatus['behind'].' commit(s) ready to deploy.',
        );
    }

    public function startDeploy(): void
    {
        $this->authorizeDeploy();

        // Re-checked rather than trusted: the state travelled through the
        // browser, and this pulls code onto the server.
        if (($this->deployStatus['upToDate'] ?? true) || ! ($this->deployStatus['ok'] ?? false)) {
            Flux::toast(variant: 'warning', text: 'Check for updates first.');

            return;
        }

        $this->deploySteps = array_map(
            fn (array $step): array => [...$step, 'state' => 'pending', 'output' => ''],
            self::DEPLOY_STEPS,
        );

        $this->deployStep = 0;
        $this->deploying = true;
    }

    /**
     * Run one step. Driven by wire:poll while a deploy is in progress.
     */
    public function runDeployStep(GitDeployer $deployer): void
    {
        $this->authorizeDeploy();

        if (! $this->deploying || ! isset($this->deploySteps[$this->deployStep])) {
            return;
        }

        $index = $this->deployStep;
        $this->deploySteps[$index]['state'] = 'running';

        $result = match ($this->deploySteps[$index]['key']) {
            'pull' => $deployer->pull((string) ($this->deployStatus['branch'] ?? 'main')),
            'migrate' => $deployer->migrate(),
            default => $deployer->refreshCaches(),
        };

        $this->deploySteps[$index]['output'] = str((string) ($result['output'] ?: $result['error']))->limit(2000)->toString();

        if (! $result['ok']) {
            $this->deploySteps[$index]['state'] = 'failed';
            $this->deploying = false;
            $this->deployHint = (string) $deployer->diagnose($result['error']);

            Flux::toast(
                variant: 'danger',
                heading: 'Deployment stopped',
                text: str((string) $result['error'])->limit(200)->toString(),
            );

            return;
        }

        $this->deploySteps[$index]['state'] = 'done';
        $this->deployStep++;

        if ($this->deployStep < count($this->deploySteps)) {
            return;
        }

        $this->deploying = false;
        $this->deployStatus = $deployer->status();

        Flux::toast(
            variant: 'success',
            heading: 'Deployed',
            text: 'The site is now running the latest code.',
        );
    }

    /**
     * Pulling code and running migrations is the most powerful thing this
     * dashboard can do, so it is checked here as well as on the route.
     */
    private function authorizeDeploy(): void
    {
        abort_unless(Gate::allows('manage settings'), 403);
    }

    public function save(): void
    {
        $validated = $this->validate();

        $values = collect($validated)
            // Blank secret fields mean "keep the stored value".
            ->reject(fn ($value, $key): bool => in_array($key, Setting::SECRETS, true) && blank($value))
            ->all();

        $values['registration_enabled'] = $this->registration_enabled;
        $values['posting_enabled'] = $this->posting_enabled;
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
            @foreach (['general' => 'cog-6-tooth', 'access' => 'lock-closed', 'mail' => 'envelope', 'analytics' => 'chart-bar', 'integrations' => 'puzzle-piece', 'deployment' => 'rocket-launch'] as $name => $icon)
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
            {{-- Access --}}
            <div class="mt-6" @if($tab !== 'access') hidden @endif>
                <flux:card class="space-y-5">
                    <flux:heading size="lg">Who can join and publish</flux:heading>

                    <flux:field variant="inline">
                        <flux:checkbox wire:model="registration_enabled" />
                        <flux:label>Allow new accounts to register</flux:label>
                        <flux:description>
                            Closing this hides the sign-up link and turns away the sign-up form and the endpoint
                            behind it. Existing accounts keep working, and you can still appoint people from
                            <a href="{{ route('admin.users') }}" class="underline" wire:navigate>User roles</a>.
                        </flux:description>
                    </flux:field>

                    <flux:separator />

                    <flux:field variant="inline">
                        <flux:checkbox wire:model="posting_enabled" />
                        <flux:label>Allow authors and moderators to post</flux:label>
                        <flux:description>
                            Switch this off to freeze the newsroom while you catch up on review. Administrators can
                            still post, so the site is never left without a way to publish.
                        </flux:description>
                    </flux:field>

                    <flux:callout variant="secondary">
                        <flux:callout.text>
                            Everyone who registers becomes an author, and an author's articles go to a moderator
                            before they appear on the site. Moderator and administrator are only ever granted from
                            the User roles screen.
                        </flux:callout.text>
                    </flux:callout>
                </flux:card>
            </div>

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

            {{-- Deployment --}}
            <div class="mt-6" @if($tab !== 'deployment') hidden @endif
                 @if ($deploying) wire:poll.800ms="runDeployStep" @endif>
                <flux:card class="space-y-5">
                    <div>
                        <flux:heading size="lg">Deploy the latest code</flux:heading>
                        <flux:text size="sm" class="mt-1">
                            Pulls from GitHub, runs any new migrations and rebuilds the caches. The site stays
                            online throughout.
                        </flux:text>
                    </div>

                    @if ($deployBlocked)
                        <flux:callout variant="danger">
                            <flux:callout.heading>Not available on this server</flux:callout.heading>
                            <flux:callout.text>{{ $deployBlocked }}</flux:callout.text>
                        </flux:callout>
                    @endif

                    @if ($deployHint || $deployError)
                        <flux:callout variant="warning">
                            <flux:callout.heading>What went wrong</flux:callout.heading>
                            <flux:callout.text>
                                {{ $deployHint ?: 'Git reported a failure. The output is below.' }}
                            </flux:callout.text>
                        </flux:callout>

                        @if ($deployError)
                            <pre class="max-h-40 overflow-auto rounded bg-zinc-900 p-3 font-mono text-[11px] leading-relaxed text-zinc-100">{{ $deployError }}</pre>
                        @endif
                    @endif

                    @if ($deployRemote)
                        <flux:text size="sm">
                            Remote: <span class="font-mono">{{ $deployRemote }}</span>
                        </flux:text>
                    @endif

                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                        <div>
                            <flux:text size="sm">Installed version</flux:text>
                            <p class="font-mono text-lg font-bold">v{{ $this->installedVersion }}</p>
                        </div>

                        @if (filled($deployStatus['latestVersion'] ?? null))
                            <flux:icon.arrow-right class="size-4 text-zinc-400" />
                            <div>
                                <flux:text size="sm">Latest release</flux:text>
                                <p class="font-mono text-lg font-bold">v{{ $deployStatus['latestVersion'] }}</p>
                            </div>

                            @if ($this->releaseAvailable)
                                <flux:badge color="amber">Update available</flux:badge>
                            @else
                                <flux:badge color="lime">Running the latest release</flux:badge>
                            @endif
                        @elseif (($deployStatus['ok'] ?? false))
                            <flux:text size="sm" class="ms-auto">
                                No version tags published yet, so only commits can be compared.
                            </flux:text>
                        @endif
                    </div>

                    @if (($deployStatus['ok'] ?? false))
                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <flux:text size="sm">
                                    Branch <span class="font-mono">{{ $deployStatus['branch'] }}</span>,
                                    currently at <span class="font-mono">{{ $deployStatus['current'] }}</span>
                                </flux:text>

                                @if ($deployStatus['upToDate'])
                                    <flux:badge size="sm" color="lime">Up to date</flux:badge>
                                @else
                                    <flux:badge size="sm" color="amber">
                                        {{ $deployStatus['behind'] }} behind
                                    </flux:badge>
                                @endif
                            </div>

                            @if (! $deployStatus['upToDate'] && filled($deployStatus['commits']))
                                <ul class="mt-3 space-y-1 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                                    @foreach ($deployStatus['commits'] as $commit)
                                        <li class="font-mono text-xs text-zinc-600 dark:text-zinc-400">{{ $commit }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    {{-- Progress --}}
                    @if (filled($deploySteps))
                        <div class="space-y-2">
                            @foreach ($deploySteps as $step)
                                <div @class([
                                    'rounded-lg border p-3',
                                    'border-zinc-200 dark:border-zinc-700' => $step['state'] === 'pending',
                                    'border-brand-400 bg-brand-50/40 dark:bg-brand-900/10' => $step['state'] === 'running',
                                    'border-lime-400 bg-lime-50/40 dark:bg-lime-900/10' => $step['state'] === 'done',
                                    'border-red-400 bg-red-50/40 dark:bg-red-900/10' => $step['state'] === 'failed',
                                ])>
                                    <div class="flex items-center gap-2 text-sm">
                                        @if ($step['state'] === 'done')
                                            <flux:icon.check-circle class="size-4 text-lime-600" />
                                        @elseif ($step['state'] === 'failed')
                                            <flux:icon.x-circle class="size-4 text-red-600" />
                                        @elseif ($step['state'] === 'running')
                                            <flux:icon.arrow-path class="size-4 animate-spin text-brand-600" />
                                        @else
                                            <flux:icon.clock class="size-4 text-zinc-400" />
                                        @endif

                                        <span @class(['font-medium' => $step['state'] !== 'pending'])>{{ $step['label'] }}</span>
                                    </div>

                                    @if (filled($step['output']))
                                        <pre class="mt-2 max-h-40 overflow-auto rounded bg-zinc-900 p-2 font-mono text-[11px] leading-relaxed text-zinc-100">{{ $step['output'] }}</pre>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex flex-wrap gap-3">
                        <flux:button type="button" variant="ghost" icon="arrow-path"
                                     wire:click="checkForUpdates" :disabled="$deploying">
                            <span wire:loading.remove wire:target="checkForUpdates">Check for updates</span>
                            <span wire:loading wire:target="checkForUpdates">Checking…</span>
                        </flux:button>

                        @if (($deployStatus['ok'] ?? false) && ! $deployStatus['upToDate'])
                            <flux:button type="button" variant="primary" icon="rocket-launch"
                                         wire:click="startDeploy" :disabled="$deploying">
                                {{ $deploying ? 'Deploying…' : 'Pull and deploy' }}
                            </flux:button>
                        @endif
                    </div>

                    <flux:callout variant="secondary">
                        <flux:callout.text>
                            Config is cleared rather than cached. Caching it would write the mail password and the
                            Google service-account key to a file in plaintext, which is the thing encrypting them
                            in the database was meant to prevent. Routes and views are still cached, which is where
                            most of the speed comes from.
                        </flux:callout.text>
                    </flux:callout>
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
