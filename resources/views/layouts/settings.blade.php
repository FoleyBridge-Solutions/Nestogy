@extends('layouts.app')

@section('content')
<div class="w-full px-6 py-6">
    <!-- Header -->
    <div class="mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">@yield('settings-title', 'Settings')</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">@yield('settings-description', 'Configure your system preferences')</p>
            </div>
            <div class="flex space-x-2">
                @yield('settings-actions')
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @if (session('success'))
        <flux:toast variant="success" class="mb-4">
            {!! session('success') !!}
        </flux:toast>
    @endif

    @if (session('error'))
        <flux:toast variant="danger" class="mb-4">
            {!! session('error') !!}
        </flux:toast>
    @endif

    @if ($errors->any())
        <flux:toast variant="danger" class="mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </flux:toast>
    @endif

    <!-- Page Content -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm">
        @yield('settings-content')
    </div>
</div>
@endsection
