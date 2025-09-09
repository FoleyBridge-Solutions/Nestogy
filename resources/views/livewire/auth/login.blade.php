<div class="w-full">
    <div class="flex flex-wrap -mx-4 min-h-screen">
        <!-- Left side - Branding -->
        <div class="w-full lg:w-1/2 hidden lg:flex items-center justify-center bg-blue-600 p-12">
            <div class="text-center text-white">
                <flux:brand logo="{{ asset('static-assets/img/branding/nestogy-logo-white.png') }}" class="justify-center mb-4" />
                <h2 class="font-bold mb-3 text-2xl">Welcome to Nestogy ERP</h2>
                <p class="text-lg mb-8">Streamline your business operations with our comprehensive ERP solution.</p>
                <div class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <flux:icon name="users" class="w-8 h-8 mx-auto mb-2" />
                        <p class="text-sm">Client Management</p>
                    </div>
                    <div>
                        <flux:icon name="ticket" class="w-8 h-8 mx-auto mb-2" />
                        <p class="text-sm">Ticket System</p>
                    </div>
                    <div>
                        <flux:icon name="chart-bar" class="w-8 h-8 mx-auto mb-2" />
                        <p class="text-sm">Financial Reports</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center py-12 px-4">
            <div class="w-full" style="max-width: 400px;">
                <div class="lg:hidden text-center mb-8">
                    <flux:brand logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" class="justify-center" />
                </div>
                <h3 class="text-center mb-6 font-bold text-2xl dark:text-white">Sign In</h3>

                <flux:card>
                    <form wire:submit="login" class="space-y-6">
                        @csrf
                        <flux:input
                            wire:model="email"
                            type="email"
                            label="Email Address"
                            placeholder="Enter your email"
                            icon="envelope"
                            required
                            autofocus
                        />

                        <flux:input
                            wire:model="password"
                            type="password"
                            label="Password"
                            placeholder="Enter your password"
                            icon="lock-closed"
                            viewable
                            required
                        />

                        <flux:input
                            wire:model="code"
                            type="text"
                            label="Two-Factor Authentication Code"
                            description="(if enabled)"
                            placeholder="Enter 6-digit code"
                            icon="shield-check"
                            maxlength="6"
                        />

                        <flux:checkbox
                            wire:model="remember"
                            label="Remember me for 30 days"
                        />

                        <div>
                            <flux:button type="submit" variant="primary" class="w-full">Sign In</flux:button>
                        </div>

                        <div class="text-center">
                            @if (Route::has('password.request'))
                                <flux:link href="{{ route('password.request') }}">
                                    Forgot your password?
                                </flux:link>
                            @endif
                        </div>
                    </form>
                </flux:card>
            </div>
        </div>
    </div>
</div>
