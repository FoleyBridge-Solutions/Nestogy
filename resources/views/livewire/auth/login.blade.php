<div class="w-full">
    <div class="flex flex-wrap -mx-4 min-h-screen">
        <!-- Left side - Branding -->
        <div class="w-full lg:w-1/2 hidden lg:flex items-center justify-center bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-12 relative overflow-hidden">
            <!-- Background decoration -->
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmZmZmYiIGZpbGwtb3BhY2l0eT0iMC4wNSI+PHBhdGggZD0iTTM2IDE2YzAtMi4yMDktMS43OTEtNC00LTRzLTQgMS43OTEtNCA0IDEuNzkxIDQgNCA0IDQtMS43OTEgNC00em0wIDI0YzAtMi4yMDktMS43OTEtNC00LTRzLTQgMS43OTEtNCA0IDEuNzkxIDQgNCA0IDQtMS43OTEgNC00ek0xMiAyOGMtMi4yMDkgMC00IDEuNzkxLTQgNHMxLjc5MSA0IDQgNCA0LTEuNzkxIDQtNC0xLjc5MS00LTQtNHptMjQgMGMtMi4yMDkgMC00IDEuNzkxLTQgNHMxLjc5MSA0IDQgNCA0LTEuNzkxIDQtNC0xLjc5MS00LTQtNHptMTIgMGMtMi4yMDkgMC00IDEuNzkxLTQgNHMxLjc5MSA0IDQgNCA0LTEuNzkxIDQtNC0xLjc5MS00LTQtNHoiLz48L2c+PC9nPjwvc3ZnPg==')] opacity-50"></div>
            
            <div class="relative z-10 max-w-lg space-y-8">
                <!-- Logo and heading -->
                <div class="text-center space-y-4">
                    <flux:brand logo="{{ asset('static-assets/img/branding/nestogy-logo-white.png') }}" class="justify-center" />
                    
                    <div class="space-y-2">
                        <flux:heading size="xl" class="text-white text-3xl font-bold">
                            Welcome to Nestogy ERP
                        </flux:heading>
                        
                        <flux:text class="text-xl text-white/90">
                            Your all-in-one business management platform
                        </flux:text>
                    </div>
                </div>

                <!-- Feature cards -->
                <div class="space-y-3">
                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20 hover:bg-white/15 transition-all duration-200">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <flux:icon name="users" class="w-6 h-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <flux:heading size="sm" class="text-white font-semibold mb-1">
                                    Client Management
                                </flux:heading>
                                <flux:text class="text-sm text-white/80">
                                    Organize and track all your client relationships in one place
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20 hover:bg-white/15 transition-all duration-200">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <flux:icon name="ticket" class="w-6 h-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <flux:heading size="sm" class="text-white font-semibold mb-1">
                                    Support Ticketing
                                </flux:heading>
                                <flux:text class="text-sm text-white/80">
                                    Provide exceptional support with our integrated ticket system
                                </flux:text>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white/10 backdrop-blur-sm rounded-lg p-4 border border-white/20 hover:bg-white/15 transition-all duration-200">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center">
                                <flux:icon name="chart-bar" class="w-6 h-6 text-white" />
                            </div>
                            <div class="flex-1">
                                <flux:heading size="sm" class="text-white font-semibold mb-1">
                                    Financial Analytics
                                </flux:heading>
                                <flux:text class="text-sm text-white/80">
                                    Make informed decisions with real-time financial insights
                                </flux:text>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Trust indicators -->
                <div class="border-t border-white/20 pt-6 mt-8">
                    <div class="flex items-center justify-center gap-8 text-white/80">
                        <div class="text-center">
                            <flux:heading size="lg" class="text-white font-bold">99.9%</flux:heading>
                            <flux:text class="text-xs text-white/70">Uptime</flux:text>
                        </div>
                        <div class="h-8 w-px bg-white/20"></div>
                        <div class="text-center">
                            <flux:heading size="lg" class="text-white font-bold">24/7</flux:heading>
                            <flux:text class="text-xs text-white/70">Support</flux:text>
                        </div>
                        <div class="h-8 w-px bg-white/20"></div>
                        <div class="text-center">
                            <flux:heading size="lg" class="text-white font-bold">
                                <flux:icon name="shield-check" class="w-5 h-5 inline" />
                            </flux:heading>
                            <flux:text class="text-xs text-white/70">Secure</flux:text>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right side - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
            <div class="w-full" style="max-width: 440px;">
                <div class="lg:hidden text-center mb-8">
                    <flux:brand logo="{{ asset('static-assets/img/branding/nestogy-logo.png') }}" class="justify-center" />
                </div>

                <flux:card class="space-y-6">
                    <div class="text-center">
                        <flux:heading size="lg">Sign in to your account</flux:heading>
                        <flux:text class="mt-2">Welcome back! Please enter your credentials.</flux:text>
                    </div>

                    <flux:separator />

                    <form wire:submit="login" class="space-y-6">
                        <flux:field>
                            <flux:label badge="Required">Email Address</flux:label>
                            <flux:input
                                wire:model="email"
                                type="email"
                                placeholder="you@example.com"
                                icon="envelope"
                                required
                                autofocus
                            />
                            <flux:error name="email" />
                        </flux:field>

                        <flux:field>
                            <div class="mb-3 flex justify-between">
                                <flux:label badge="Required">Password</flux:label>
                                @if (Route::has('password.request'))
                                    <flux:link href="{{ route('password.request') }}" variant="subtle" class="text-sm">
                                        Forgot password?
                                    </flux:link>
                                @endif
                            </div>
                            <flux:input
                                wire:model="password"
                                type="password"
                                placeholder="Enter your password"
                                icon="lock-closed"
                                viewable
                                required
                            />
                            <flux:error name="password" />
                        </flux:field>

                        <flux:field>
                            <flux:label badge="Optional">Two-Factor Authentication</flux:label>
                            <flux:input
                                wire:model="code"
                                type="text"
                                placeholder="Enter 6-digit code"
                                icon="shield-check"
                                maxlength="6"
                            />
                            <flux:description>Only required if 2FA is enabled on your account.</flux:description>
                            <flux:error name="code" />
                        </flux:field>

                        <flux:checkbox
                            wire:model.defer="remember"
                            label="Remember me for 30 days"
                        />

                        <flux:separator />

                        <div class="space-y-2">
                            <flux:button type="submit" variant="primary" class="w-full">
                                Sign In
                            </flux:button>
                        </div>
                    </form>
                </flux:card>

                <div class="mt-6 text-center">
                    <flux:text class="text-sm">
                        Need help? <flux:link href="mailto:support@nestogy.com" variant="subtle">Contact Support</flux:link>
                    </flux:text>
                </div>
            </div>
        </div>
    </div>
</div>
