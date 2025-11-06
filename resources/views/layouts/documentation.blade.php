<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ $title ?? 'Nestogy Documentation' }}</title>
    <meta name="description" content="Comprehensive user documentation for Nestogy ERP - MSP management platform">
    
    {{-- SEO --}}
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">
    
    {{-- Open Graph --}}
    <meta property="og:title" content="{{ $title ?? 'Nestogy Documentation' }}">
    <meta property="og:description" content="Comprehensive user documentation for Nestogy ERP">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    
    {{-- Favicon --}}
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    
    {{-- Fonts --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    
    {{-- Scripts --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
    
    {{-- Print Styles --}}
    <style media="print">
        header, aside, .no-print { display: none !important; }
        main { margin: 0 !important; max-width: 100% !important; }
    </style>
</head>
<body class="h-full font-sans antialiased bg-white dark:bg-zinc-950">
    
    {{-- Header --}}
    <header class="sticky top-0 z-50 border-b border-zinc-200 dark:border-zinc-800 bg-white/95 dark:bg-zinc-900/95 backdrop-blur supports-[backdrop-filter]:bg-white/60 dark:supports-[backdrop-filter]:bg-zinc-900/60">
        <div class="mx-auto flex h-16 max-w-7xl items-center justify-between px-4 sm:px-6 lg:px-8">
            {{-- Logo --}}
            <div class="flex items-center gap-4">
                <a href="{{ route('docs.index') }}" wire:navigate class="flex items-center gap-2 text-xl font-bold text-zinc-900 dark:text-white hover:text-zinc-700 dark:hover:text-zinc-300 transition">
                    <flux:icon name="book-open" class="size-6" />
                    <span>Nestogy Docs</span>
                </a>
            </div>
            
            {{-- Search --}}
            <div class="flex-1 max-w-md mx-4">
                <livewire:documentation.documentation-search />
            </div>
            
            {{-- Auth Links --}}
            <div class="flex items-center gap-4">
                @auth
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <flux:button variant="ghost" size="sm" icon="arrow-left">
                            Back to App
                        </flux:button>
                    </a>
                @else
                    <a href="{{ route('login') }}">
                        <flux:button variant="primary" size="sm">
                            Login
                        </flux:button>
                    </a>
                @endauth
            </div>
        </div>
    </header>
    
    {{-- Main Content --}}
    <div class="flex min-h-[calc(100vh-4rem)]">
        {{-- Sidebar Navigation --}}
        <aside class="hidden lg:block w-64 border-r border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 fixed h-[calc(100vh-4rem)] overflow-y-auto">
            <livewire:documentation.documentation-navigation :current-page="$page ?? null" />
        </aside>
        
        {{-- Content --}}
        <main class="flex-1 lg:ml-64">
            <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12">
                {{ $slot }}
            </div>
        </main>
    </div>
    
    {{-- Footer --}}
    <footer class="border-t border-zinc-200 dark:border-zinc-800 bg-zinc-50 dark:bg-zinc-900 lg:ml-64 no-print">
        <div class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4 text-sm text-zinc-600 dark:text-zinc-400">
                <div>
                    © {{ date('Y') }} Nestogy ERP. All rights reserved.
                </div>
                <div class="flex gap-4">
                    <a href="mailto:support@nestogy.com" class="hover:text-zinc-900 dark:hover:text-zinc-100 transition">
                        Support
                    </a>
                    <span>•</span>
                    <a href="{{ route('docs.show', 'faq') }}" wire:navigate class="hover:text-zinc-900 dark:hover:text-zinc-100 transition">
                        FAQ
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    @livewireScripts
</body>
</html>
