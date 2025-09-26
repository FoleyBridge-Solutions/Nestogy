<div>
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Settings</h1>
        <p class="text-gray-500">Configure your system preferences and integrations</p>
    </div>

    {{-- Settings Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($categories as $key => $category)
            @php
                $colors = [
                    'general' => ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
                    'email' => ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                    'billing' => ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
                    'integrations' => ['bg' => 'bg-orange-100', 'text' => 'text-orange-600'],
                    'security' => ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
                    'tickets' => ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
                    'projects' => ['bg' => 'bg-indigo-100', 'text' => 'text-indigo-600'],
                    'assets' => ['bg' => 'bg-cyan-100', 'text' => 'text-cyan-600'],
                    'contracts' => ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
                    'automation' => ['bg' => 'bg-amber-100', 'text' => 'text-amber-600'],
                    'api' => ['bg' => 'bg-slate-100', 'text' => 'text-slate-600'],
                    'data' => ['bg' => 'bg-emerald-100', 'text' => 'text-emerald-600'],
                ];
                $color = $colors[$key] ?? ['bg' => 'bg-gray-100', 'text' => 'text-gray-600'];
            @endphp
            <flux:card class="hover:shadow-lg transition-shadow cursor-pointer" onclick="window.location.href='{{ route($category['route']) }}'">
                <div class="p-6">
                    <div class="flex items-start gap-4">
                        <div class="p-3 {{ $color['bg'] }} rounded-lg">
                            <flux:icon name="{{ $category['icon'] }}" class="size-6 {{ $color['text'] }}" />
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
        <h2 class="text-xl font-semibold mb-6">Quick Settings</h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {{-- General --}}
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ route('settings.category.show', ['company', 'general']) }}'">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-green-100 rounded-lg">
                            <flux:icon name="cog-6-tooth" class="size-5 text-green-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm mb-1">General</h3>
                            <p class="text-xs text-gray-500">Company information and basic settings</p>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Email --}}
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ route('settings.category.show', ['communication', 'email']) }}'">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-blue-100 rounded-lg">
                            <flux:icon name="envelope" class="size-5 text-blue-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm mb-1">Email Configuration</h3>
                            <p class="text-xs text-gray-500">SMTP and mail provider settings</p>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Billing & Financial --}}
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ route('settings.category.show', ['financial', 'billing']) }}'">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-purple-100 rounded-lg">
                            <flux:icon name="banknotes" class="size-5 text-purple-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm mb-1">Billing & Financial</h3>
                            <p class="text-xs text-gray-500">Billing, invoicing, and payment settings</p>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Integrations --}}
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ route('settings.domain.index', 'integrations') }}'">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-orange-100 rounded-lg">
                            <flux:icon name="puzzle-piece" class="size-5 text-orange-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm mb-1">Integrations</h3>
                            <p class="text-xs text-gray-500">Third-party service integrations</p>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Security --}}
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ route('settings.domain.index', 'security') }}'">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-red-100 rounded-lg">
                            <flux:icon name="shield-check" class="size-5 text-red-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm mb-1">Security</h3>
                            <p class="text-xs text-gray-500">Security and access control settings</p>
                        </div>
                    </div>
                </div>
            </flux:card>

            {{-- Tickets --}}
            <flux:card class="hover:shadow-md transition-shadow cursor-pointer" onclick="window.location.href='{{ route('settings.category.show', ['operations', 'tickets']) }}'">
                <div class="p-4">
                    <div class="flex items-start gap-3">
                        <div class="p-2 bg-yellow-100 rounded-lg">
                            <flux:icon name="ticket" class="size-5 text-yellow-600" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-medium text-sm mb-1">Tickets</h3>
                            <p class="text-xs text-gray-500">Ticket system configuration</p>
                        </div>
                    </div>
                </div>
            </flux:card>
        </div>
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