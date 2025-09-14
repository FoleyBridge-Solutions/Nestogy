<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Settings</h1>
        <p class="text-gray-500">Configure your system preferences and integrations</p>
    </div>

    {{-- Settings Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $key => $category)
            <flux:card class="hover:shadow-lg transition-shadow cursor-pointer" onclick="window.location.href='{{ route($category['route']) }}'">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 bg-blue-100 rounded-lg">
                            <flux:icon.{{ $category['icon'] }} class="size-6 text-blue-600" />
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-semibold mb-1">{{ $category['name'] }}</h3>
                            <p class="text-sm text-gray-600">{{ $category['description'] }}</p>
                        </div>
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    {{-- Quick Settings Section --}}
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">Quick Settings</h2>
        
        <flux:card>
            <div class="p-6 space-y-4">
                {{-- Company Information --}}
                <div class="pb-4 border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">Company Information</h3>
                            <p class="text-sm text-gray-500">Update your company name, logo, and contact details</p>
                        </div>
                        <flux:button variant="outline" size="sm" href="{{ route('settings.general') }}">
                            Configure
                        </flux:button>
                    </div>
                </div>

                {{-- Email Settings --}}
                <div class="pb-4 border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">Email Configuration</h3>
                            <p class="text-sm text-gray-500">Configure SMTP settings and email templates</p>
                        </div>
                        <flux:button variant="outline" size="sm" href="{{ route('settings.email') }}">
                            Configure
                        </flux:button>
                    </div>
                </div>

                {{-- Billing Settings --}}
                <div class="pb-4 border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">Billing & Invoicing</h3>
                            <p class="text-sm text-gray-500">Set up payment methods, tax rates, and invoice templates</p>
                        </div>
                        <flux:button variant="outline" size="sm" href="{{ route('settings.billing-financial') }}">
                            Configure
                        </flux:button>
                    </div>
                </div>

                {{-- Integration Status --}}
                <div class="pb-4 border-b">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">Integrations</h3>
                            <p class="text-sm text-gray-500">Connect with third-party services and APIs</p>
                        </div>
                        <flux:button variant="outline" size="sm" href="{{ route('settings.integrations') }}">
                            Manage
                        </flux:button>
                    </div>
                </div>

                {{-- Security Settings --}}
                <div>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="font-medium">Security & Access</h3>
                            <p class="text-sm text-gray-500">Manage user roles, permissions, and security policies</p>
                        </div>
                        <flux:button variant="outline" size="sm" href="{{ route('settings.security') }}">
                            Configure
                        </flux:button>
                    </div>
                </div>
            </div>
        </flux:card>
    </div>

    {{-- System Status --}}
    <div class="mt-8">
        <h2 class="text-xl font-semibold mb-4">System Status</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Database</div>
                            <div class="font-medium">Connected</div>
                        </div>
                        <flux:badge variant="success">Healthy</flux:badge>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Email Service</div>
                            <div class="font-medium">SMTP Configured</div>
                        </div>
                        <flux:badge variant="success">Active</flux:badge>
                    </div>
                </div>
            </flux:card>

            <flux:card>
                <div class="p-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <div class="text-sm text-gray-500">Queue Workers</div>
                            <div class="font-medium">Running</div>
                        </div>
                        <flux:badge variant="success">Online</flux:badge>
                    </div>
                </div>
            </flux:card>
        </div>
    </div>
</div>