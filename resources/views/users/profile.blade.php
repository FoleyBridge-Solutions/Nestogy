@extends('layouts.app')

@section('content')
<div class="p-6">
    {{-- Success Message --}}
    @if (session('success'))
        <div class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
            <p class="text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Profile Information --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Profile Information</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Update your account's profile information and email address.</p>
        </div>
        <form method="POST" action="{{ route('user-profile-information.update') }}" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name</label>
                    <input type="text" name="name" id="name" value="{{ old('name', auth()->user()->name) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Phone</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone', auth()->user()->phone) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('phone')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                    <input type="text" name="title" id="title" value="{{ old('title', auth()->user()->title) }}"
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('title')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Save
                </button>
            </div>
        </form>
    </div>

    {{-- Update Password --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Update Password</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Ensure your account is using a long, random password to stay secure.</p>
        </div>
        <form method="POST" action="{{ route('user-password.update') }}" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="space-y-4">
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
                    <input type="password" name="current_password" id="current_password" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                    <input type="password" name="password" id="password" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                    <input type="password" name="password_confirmation" id="password_confirmation" required
                           class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Update Password
                </button>
            </div>
        </form>
    </div>

    {{-- Two Factor Authentication --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Two Factor Authentication</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Add additional security to your account using two factor authentication.</p>
        </div>
        <div class="p-6">
            @if (auth()->user()->two_factor_secret)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Two factor authentication is now enabled. Scan the following QR code using your phone's authenticator application.
                </p>
                
                <div class="mb-4">
                    {!! auth()->user()->twoFactorQrCodeSvg() !!}
                </div>

                <form method="POST" action="{{ route('two-factor.disable') }}" class="inline">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Disable
                    </button>
                </form>
            @else
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                    Two factor authentication is not currently enabled.
                </p>
                
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        Enable
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- Browser Sessions --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Browser Sessions</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Manage and log out your active sessions on other browsers and devices.</p>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                If necessary, you may log out of all of your other browser sessions across all of your devices.
            </p>
            
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Log Out All Sessions
                </button>
            </form>
        </div>
    </div>

    {{-- Preferences --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow mb-6">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Preferences</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Customize your experience with personal preferences.</p>
        </div>
        <form method="POST" action="{{ route('users.preferences.update') }}" class="p-6">
            @csrf
            @method('PUT')
            
            <div class="space-y-6">
                {{-- Theme Preference --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Theme</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="theme" value="light" 
                                   {{ old('theme', auth()->user()->userSetting->preferences['theme'] ?? 'light') == 'light' ? 'checked' : '' }}
                                   class="mr-2 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Light</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="theme" value="dark"
                                   {{ old('theme', auth()->user()->userSetting->preferences['theme'] ?? 'light') == 'dark' ? 'checked' : '' }}
                                   class="mr-2 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">Dark</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="theme" value="system"
                                   {{ old('theme', auth()->user()->userSetting->preferences['theme'] ?? 'light') == 'system' ? 'checked' : '' }}
                                   class="mr-2 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">System</span>
                        </label>
                    </div>
                </div>

                {{-- Language --}}
                <div>
                    <label for="language" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Language</label>
                    <select name="language" id="language" 
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="en" {{ old('language', auth()->user()->userSetting->preferences['language'] ?? 'en') == 'en' ? 'selected' : '' }}>English</option>
                        <option value="es" {{ old('language', auth()->user()->userSetting->preferences['language'] ?? 'en') == 'es' ? 'selected' : '' }}>Spanish</option>
                        <option value="fr" {{ old('language', auth()->user()->userSetting->preferences['language'] ?? 'en') == 'fr' ? 'selected' : '' }}>French</option>
                        <option value="de" {{ old('language', auth()->user()->userSetting->preferences['language'] ?? 'en') == 'de' ? 'selected' : '' }}>German</option>
                    </select>
                </div>

                {{-- Timezone --}}
                <div>
                    <label for="timezone" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Timezone</label>
                    <select name="timezone" id="timezone"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach(timezone_identifiers_list() as $timezone)
                            <option value="{{ $timezone }}" {{ old('timezone', auth()->user()->userSetting->preferences['timezone'] ?? 'UTC') == $timezone ? 'selected' : '' }}>
                                {{ $timezone }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Date Format --}}
                <div>
                    <label for="date_format" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Date Format</label>
                    <select name="date_format" id="date_format"
                            class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="m/d/Y" {{ old('date_format', auth()->user()->userSetting->preferences['date_format'] ?? 'm/d/Y') == 'm/d/Y' ? 'selected' : '' }}>MM/DD/YYYY</option>
                        <option value="d/m/Y" {{ old('date_format', auth()->user()->userSetting->preferences['date_format'] ?? 'm/d/Y') == 'd/m/Y' ? 'selected' : '' }}>DD/MM/YYYY</option>
                        <option value="Y-m-d" {{ old('date_format', auth()->user()->userSetting->preferences['date_format'] ?? 'm/d/Y') == 'Y-m-d' ? 'selected' : '' }}>YYYY-MM-DD</option>
                    </select>
                </div>

                {{-- Time Format --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Time Format</label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="time_format" value="12"
                                   {{ old('time_format', auth()->user()->userSetting->preferences['time_format'] ?? '12') == '12' ? 'checked' : '' }}
                                   class="mr-2 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">12-hour</span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="time_format" value="24"
                                   {{ old('time_format', auth()->user()->userSetting->preferences['time_format'] ?? '12') == '24' ? 'checked' : '' }}
                                   class="mr-2 text-indigo-600 border-gray-300 focus:ring-indigo-500">
                            <span class="text-sm text-gray-700 dark:text-gray-300">24-hour</span>
                        </label>
                    </div>
                </div>

                {{-- Email Notifications --}}
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="email_notifications" value="1"
                               {{ old('email_notifications', auth()->user()->userSetting->preferences['email_notifications'] ?? true) ? 'checked' : '' }}
                               class="mr-2 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Email Notifications</span>
                    </label>
                </div>

                {{-- Desktop Notifications --}}
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="desktop_notifications" value="1"
                               {{ old('desktop_notifications', auth()->user()->userSetting->preferences['desktop_notifications'] ?? true) ? 'checked' : '' }}
                               class="mr-2 rounded text-indigo-600 border-gray-300 focus:ring-indigo-500">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Desktop Notifications</span>
                    </label>
                </div>
            </div>

            <div class="mt-6">
                <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                    Save Preferences
                </button>
            </div>
        </form>
    </div>

    {{-- Delete Account --}}
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
        <div class="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Delete Account</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Permanently delete your account.</p>
        </div>
        <div class="p-6">
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.
            </p>
            
            <button type="button" onclick="if(confirm('Are you sure you want to delete your account? This action cannot be undone.')) document.getElementById('delete-account-form').submit();"
                    class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                Delete Account
            </button>
            
            <form id="delete-account-form" method="POST" action="{{ route('users.account.destroy') }}" class="hidden">
                @csrf
                @method('DELETE')
            </form>
        </div>
    </div>
</div>
@endsection
