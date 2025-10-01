<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title') - {{ config('app.name') }} Client Portal</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="min-h-screen bg-white dark:bg-zinc-800 font-sans antialiased">
    @fluxAppearance
    
    <flux:header class="bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />
        
        <flux:brand href="{{ route('client.dashboard') }}" name="{{ config('app.name') }}" class="max-lg:hidden" />
        
        <flux:spacer />
        
        <flux:navbar class="me-4">
            <flux:navbar.item icon="bell" href="#" label="Notifications" />
            <flux:navbar.item icon="cog-6-tooth" href="#" label="Settings" class="max-lg:hidden" />
        </flux:navbar>
        
        <flux:dropdown position="top" align="start">
            <flux:profile avatar="{{ auth('client')->check() ? 'https://ui-avatars.com/api/?name='.urlencode(auth('client')->user()->name) : '' }}" />
            <flux:menu>
                <flux:menu.heading>{{ auth('client')->user()->name ?? 'Account' }}</flux:menu.heading>
                <flux:menu.item icon="user" href="{{ route('client.profile') }}">Profile</flux:menu.item>
                <flux:menu.item icon="cog-6-tooth" href="{{ route('client.profile') }}">Settings</flux:menu.item>
                <flux:menu.separator />
                <flux:menu.item icon="arrow-right-start-on-rectangle">
                    <form action="{{ route('client.logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full text-left">Logout</button>
                    </form>
                </flux:menu.item>
            </flux:menu>
        </flux:dropdown>
    </flux:header>
    
    <flux:sidebar sticky collapsible="mobile" class="bg-zinc-50 dark:bg-zinc-900 border-r border-zinc-200 dark:border-zinc-700">
        <flux:sidebar.header>
            <flux:sidebar.brand href="{{ route('client.dashboard') }}" name="{{ config('app.name') }}" />
        </flux:sidebar.header>

        <flux:sidebar.nav>
            @php
                $contact = auth('client')->user();
                $permissions = $contact->portal_permissions ?? [];
            @endphp

            <flux:sidebar.item href="{{ route('client.dashboard') }}" icon="home" current="{{ request()->routeIs('client.dashboard') }}">
                Dashboard
            </flux:sidebar.item>

            @if(in_array('can_view_contracts', $permissions))
                <flux:sidebar.item href="{{ route('client.contracts') }}" icon="document-text" current="{{ request()->routeIs('client.contracts*') }}">
                    Contracts
                </flux:sidebar.item>
            @endif

            @if(in_array('can_view_invoices', $permissions))
                <flux:sidebar.item href="{{ route('client.invoices') }}" icon="banknotes" current="{{ request()->routeIs('client.invoices*') }}">
                    Invoices
                </flux:sidebar.item>
            @endif

            @if(in_array('can_view_tickets', $permissions))
                <flux:sidebar.item href="{{ route('client.tickets') }}" icon="lifebuoy" current="{{ request()->routeIs('client.tickets*') }}">
                    Support
                </flux:sidebar.item>
            @endif

            @if(in_array('can_approve_quotes', $permissions))
                <flux:sidebar.item href="{{ Route::has('client.quotes') ? route('client.quotes') : '#' }}" icon="clipboard-document-check" current="{{ request()->routeIs('client.quotes*') }}">
                    Quotes
                </flux:sidebar.item>
            @endif

            @if(in_array('can_view_assets', $permissions))
                <flux:sidebar.item href="{{ route('client.assets') }}" icon="server-stack" current="{{ request()->routeIs('client.assets*') }}">
                    Assets
                </flux:sidebar.item>
            @endif

            @if(in_array('can_view_projects', $permissions))
                <flux:sidebar.item href="{{ Route::has('client.projects') ? route('client.projects') : '#' }}" icon="briefcase" current="{{ request()->routeIs('client.projects*') }}">
                    Projects
                </flux:sidebar.item>
            @endif

            @if(in_array('can_view_reports', $permissions))
                <flux:sidebar.item href="{{ Route::has('client.reports') ? route('client.reports') : '#' }}" icon="chart-bar" current="{{ request()->routeIs('client.reports*') }}">
                    Reports
                </flux:sidebar.item>
            @endif
        </flux:sidebar.nav>
        
        <flux:sidebar.spacer />
        
        <flux:sidebar.nav>
            <flux:sidebar.item href="{{ route('client.profile') }}" icon="cog-6-tooth" current="{{ request()->routeIs('client.profile*') }}">
                Settings
            </flux:sidebar.item>
        </flux:sidebar.nav>
    </flux:sidebar>

    <flux:main class="px-6 py-6">
            @if(session('success'))
                <flux:callout variant="success" icon="check-circle" heading="{{ session('success') }}" />
            @endif

            @if(session('error'))
                <flux:callout variant="danger" icon="x-circle" heading="{{ session('error') }}" />
            @endif

            @if(session('warning'))
                <flux:callout variant="warning" icon="exclamation-circle" heading="{{ session('warning') }}" />
            @endif

            @if(session('info'))
                <flux:callout variant="secondary" icon="information-circle" heading="{{ session('info') }}" />
            @endif

        @yield('content')
    </flux:main>

    @stack('scripts')
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    
    @fluxScripts
</body>
</html>
