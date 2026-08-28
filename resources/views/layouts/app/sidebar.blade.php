<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800">

    <x-impersonation-banner />
    <flux:sidebar sticky collapsible="mobile"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
        <flux:sidebar.header>
            <x-app-logo :sidebar="true" href="{{ route('dashboard') }}" wire:navigate />
            <flux:sidebar.collapse class="lg:hidden" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            <flux:sidebar.group :heading="__('Platform')" class="grid">
                <flux:sidebar.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate>
                    {{ __('Dashboard') }}
                </flux:sidebar.item>
            </flux:sidebar.group>

            <flux:sidebar.group :heading="__('Newsroom')" class="grid">
                @can('manage news')
                    <flux:sidebar.item icon="newspaper" :href="route('admin.posts')"
                        :current="request()->routeIs('admin.posts')" wire:navigate>
                        {{ __('News') }}
                    </flux:sidebar.item>
                @endcan

                @can('moderate posts')
                    <flux:sidebar.item icon="document-check" :href="route('admin.moderation')"
                        :current="request()->routeIs('admin.moderation')" wire:navigate>
                        {{ __('Moderation') }}

                        @if ($awaiting = \App\Models\Post::awaitingReview()->count())
                            <flux:badge size="sm" color="amber" class="ms-auto">{{ $awaiting }}</flux:badge>
                        @endif
                    </flux:sidebar.item>
                @endcan
            </flux:sidebar.group>

            @canany(['manage homepage', 'manage services', 'manage classes', 'manage gallery', 'manage testimonials'])
                <flux:sidebar.group :heading="__('Content')" class="grid">
                    @can('manage homepage')
                        <flux:sidebar.item icon="home-modern" :href="route('admin.homepage')"
                            :current="request()->routeIs('admin.homepage')" wire:navigate>
                            {{ __('Homepage') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage services')
                        <flux:sidebar.item icon="briefcase" :href="route('admin.services')"
                            :current="request()->routeIs('admin.services')" wire:navigate>
                            {{ __('Services') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage classes')
                        <flux:sidebar.item icon="academic-cap" :href="route('admin.classes')"
                            :current="request()->routeIs('admin.classes')" wire:navigate>
                            {{ __('Online Classes') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage gallery')
                        <flux:sidebar.item icon="photo" :href="route('admin.gallery')"
                            :current="request()->routeIs('admin.gallery')" wire:navigate>
                            {{ __('Gallery') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage testimonials')
                        <flux:sidebar.item icon="chat-bubble-left-right" :href="route('admin.testimonials')"
                            :current="request()->routeIs('admin.testimonials')" wire:navigate>
                            {{ __('Testimonials') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany

            @canany(['manage messages', 'manage contact', 'manage subscribers', 'manage newsletters'])
                <flux:sidebar.group :heading="__('Audience')" class="grid">
                    @can('manage messages')
                        <flux:sidebar.item icon="inbox" :href="route('admin.messages')"
                            :current="request()->routeIs('admin.messages')" wire:navigate>
                            {{ __('Messages') }}

                            @if ($unread = \App\Models\ContactMessage::whereNull('read_at')->count())
                                <flux:badge size="sm" color="red" class="ms-auto">{{ $unread }}</flux:badge>
                            @endif
                        </flux:sidebar.item>
                    @endcan

                    @can('manage newsletters')
                        <flux:sidebar.item icon="envelope-open" :href="route('admin.newsletters')"
                            :current="request()->routeIs('admin.newsletters') || request()->routeIs('admin.newsletter-*')" wire:navigate>
                            {{ __('Newsletters') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage subscribers')
                        <flux:sidebar.item icon="user-group" :href="route('admin.subscribers')"
                            :current="request()->routeIs('admin.subscribers')" wire:navigate>
                            {{ __('Subscribers') }}
                        </flux:sidebar.item>
                    @endcan

                    @can('manage contact')
                        <flux:sidebar.item icon="cog-6-tooth" :href="route('admin.contact')"
                            :current="request()->routeIs('admin.contact')" wire:navigate>
                            {{ __('Contact Settings') }}
                        </flux:sidebar.item>
                    @endcan
                </flux:sidebar.group>
            @endcanany

            <flux:sidebar.group :heading="__('Site')" class="grid">
                @can('view analytics')
                    <flux:sidebar.item icon="chart-bar" :href="route('admin.analytics')"
                        :current="request()->routeIs('admin.analytics')" wire:navigate>
                        {{ __('Analytics') }}
                    </flux:sidebar.item>
                @endcan

                @can('manage roles')
                    <flux:sidebar.item icon="users" :href="route('admin.users')"
                        :current="request()->routeIs('admin.users')" wire:navigate>
                        {{ __('User Roles') }}
                    </flux:sidebar.item>
                @endcan

                @can('manage settings')
                    <flux:sidebar.item icon="wrench-screwdriver" :href="route('admin.settings')"
                        :current="request()->routeIs('admin.settings')" wire:navigate>
                        {{ __('Settings') }}
                    </flux:sidebar.item>
                @endcan

                <flux:sidebar.item icon="globe-alt" :href="route('home')" target="_blank">
                    {{ __('View Website') }}
                </flux:sidebar.item>
            </flux:sidebar.group>
        </flux:sidebar.nav>

        <flux:spacer />

        <x-desktop-user-menu class="hidden lg:block" :name="auth()->user()->name" />
    </flux:sidebar>

    <!-- Mobile User Menu -->
    <flux:header class="lg:hidden">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

        <flux:spacer />

        <flux:dropdown position="top" align="end">
            <flux:profile :initials="auth()->user()->initials()" icon-trailing="chevron-down" />

            <flux:menu>
                <flux:menu.radio.group>
                    <div class="p-0 text-sm font-normal">
                        <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                            <flux:avatar :name="auth()->user()->name" :initials="auth()->user()->initials()" />

                            <div class="grid flex-1 text-start text-sm leading-tight">
                                <flux:heading class="truncate">{{ auth()->user()->name }}</flux:heading>
                                <flux:text class="truncate">{{ auth()->user()->email }}</flux:text>
                            </div>
                        </div>
                    </div>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <flux:menu.radio.group>
                    <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>
                        {{ __('Settings') }}
                    </flux:menu.item>
                </flux:menu.radio.group>

                <flux:menu.separator />

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                        class="w-full cursor-pointer" data-test="logout-button">
                        {{ __('Log out') }}
                    </flux:menu.item>
                </form>
            </flux:menu>
        </flux:dropdown>
    </flux:header>

    {{ $slot }}

    @persist('toast')
        <flux:toast.group>
            <flux:toast />
        </flux:toast.group>
    @endpersist

    <x-page-loader />

    @fluxScripts
</body>

</html>
