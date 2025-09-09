@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-gray-900">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-4">
                    <!-- Mobile Menu Button -->
                    <div class="lg:hidden">
                        <flux:modal.trigger name="settings-lazy-menu-mobile">
                            <flux:button icon="bars-3" variant="ghost" />
                        </flux:modal.trigger>
                    </div>
                    <div>
                        <flux:heading size="xl">@yield('settings-title', 'Settings')</flux:heading>
                        <flux:text class="text-gray-600 dark:text-gray-300 mt-1">@yield('settings-description', 'Configure your system preferences')</flux:text>
                    </div>
                </div>
                <div class="flex space-x-2">
                    @yield('settings-actions')
                </div>
            </div>
        </div>

        <!-- Main Content Area -->
        <div class="lg:grid lg:grid-cols-12 lg:gap-6">
            <!-- Desktop Sidebar Navigation -->
            <div class="hidden lg:block lg:col-span-12-span-3">
                @include('settings.partials.navigation', ['mobile' => false])
            </div>

            <!-- Settings Content -->
            <div class="lg:col-span-12-span-9 mt-6 lg:mt-0">
                <!-- Flash Messages -->
                @if (session('success'))
                    <flux:toast variant="success" class="mb-4 w-full">
                        <p>{!! session('success') !!}</p>
                    </flux:toast>
                @endif

                @if (session('error'))
                    <flux:toast variant="danger" class="mb-4 w-full">
                        <p>{!! session('error') !!}</p>
                    </flux:toast>
                @endif

                @if ($errors->any())
                    <flux:toast variant="danger" class="mb-4 w-full">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </flux:toast>
                @endif

                <!-- Content Container - Using Livewire lazy loading if needed -->
                <flux:card class="min-h-[500px]">
                    @if(isset($lazyLoad) && $lazyLoad)
                        @livewire($settingsComponent ?? 'settings.general', ['section' => $currentSection ?? 'general'], key($currentSection ?? 'general'))
                    @else
                        @yield('settings-content')
                    @endif
                </flux:card>
            </div>
        </div>
    </div>
</div>

<!-- Mobile Sidebar Modal -->
<flux:modal name="settings-lazy-menu-mobile" variant="flyout" position="left" class="w-80">
    <div class="flex flex-col-span-12 h-full">
        <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <flux:heading size="lg">Settings Menu</flux:heading>
            <flux:modal.close>
                <flux:button variant="ghost" icon="x-mark" />
            </flux:modal.close>
        </div>
        <div class="flex-1 overflow-y-auto">
            @include('settings.partials.navigation', ['mobile' => true])
        </div>
    </div>
</flux:modal>

<!-- Notifications will be handled by Livewire -->

@endsection
