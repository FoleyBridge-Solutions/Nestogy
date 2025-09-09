<div class="w-full max-w-4xl mx-auto">
    <flux:card>
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
            <flux:heading size="lg">Register New User</flux:heading>
        </div>
        <form wire:submit="register" class="p-6">
            @csrf
            <div class="md:grid md:grid-cols-2 md:gap-x-6">
                <!-- Personal Information -->
                <div class="space-y-6">
                    <flux:heading>
                        Personal Information
                    </flux:heading>

                    <flux:input wire:model="name" label="Full Name *" placeholder="Enter full name" required />
                    <flux:input wire:model="email" type="email" label="Email Address *" placeholder="Enter email address" required />
                    <flux:input wire:model="extension_key" label="Extension Key" placeholder="Optional phone extension" maxlength="18" />
                </div>

                <!-- Account Settings -->
                <div class="space-y-6 mt-6 md:mt-0">
                    <flux:heading>
                        Account Settings
                    </flux:heading>

                    <flux:select wire:model="company_id" label="Company *" placeholder="Select Company" required>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="role" label="User Role *" placeholder="Select Role" required
                                 description="Admin: Full access. Technician: Tickets. Accountant: Finances.">
                        @foreach($roles as $roleId => $roleLabel)
                            <option value="{{ $roleLabel }}">{{ $roleLabel }}</option>
                        @endforeach
                    </flux:select>

                    <flux:switch wire:model="status" label="Active User Account" description="Inactive users cannot log in." />
                    <flux:switch wire:model="force_mfa" label="Require Multi-Factor Authentication" description="User must set up 2FA on first login." />
                </div>
            </div>

            <!-- Password Section -->
            <hr class="my-6 dark:border-gray-700">
            <div class="space-y-6">
                <flux:heading>Password</flux:heading>
                <div class="md:grid md:grid-cols-2 md:gap-x-6">
                    <flux:input wire:model="password" type="password" label="Password *" placeholder="Enter password" required viewable description="Minimum 8 characters required." />
                    <flux:input wire:model="password_confirmation" type="password" label="Confirm Password *" placeholder="Confirm password" required viewable />
                </div>
            </div>

            <!-- Submit Buttons -->
            <div class="flex justify-between mt-8">
                <flux:button href="{{ route('users.index') }}" variant="ghost">Cancel</flux:button>
                <flux:button type="submit" variant="primary">Create User</flux:button>
            </div>
        </form>
    </flux:card>
</div>
