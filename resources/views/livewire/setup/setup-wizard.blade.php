<div class="max-w-4xl mx-auto">
    <!-- Header Section -->
    <div class="text-center mb-12">
        <div class="flex justify-center mb-6">
            <div class="bg-blue-600 dark:bg-blue-500 rounded-full p-6 shadow-lg">
                <svg class="h-12 w-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
            </div>
        </div>
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white mb-4">
            Set Up Your MSP ERP System
        </h1>
        <p class="text-xl text-gray-600 dark:text-gray-300">
            Complete configuration for your managed service provider business
        </p>
    </div>

    <!-- Progress Steps -->
    <div class="mb-12">
        <nav aria-label="Progress">
            <ol class="flex items-center justify-center">
                @php
                    $stepLabels = [1 => 'Company Info', 2 => 'Email Setup', 3 => 'System Prefs', 4 => 'MSP Settings', 5 => 'Admin User'];
                @endphp
                
                @for ($i = 1; $i <= $totalSteps; $i++)
                    <li class="relative {{ $i < $totalSteps ? 'pr-8 sm:pr-20' : '' }}">
                        <div class="flex items-center">
                            <button 
                                wire:click="goToStep({{ $i }})"
                                class="flex items-center justify-center w-10 h-10 {{ $currentStep >= $i ? 'bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-800' : 'bg-gray-300 dark:bg-gray-600 ring-4 ring-gray-100 dark:ring-gray-700' }} rounded-full transition-all duration-200 {{ $this->canNavigateToStep($i) ? 'cursor-pointer hover:scale-105' : 'cursor-not-allowed' }}"
                                {{ !$this->canNavigateToStep($i) ? 'disabled' : '' }}>
                                @if (in_array($i, $completedSteps))
                                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @else
                                    <span class="{{ $currentStep >= $i ? 'text-white' : 'text-gray-600 dark:text-gray-300' }} font-medium text-sm">{{ $i }}</span>
                                @endif
                            </button>
                            <span class="ml-4 text-sm font-medium {{ $currentStep >= $i ? 'text-gray-900 dark:text-white' : 'text-gray-500 dark:text-gray-400' }}">{{ $stepLabels[$i] }}</span>
                        </div>
                        @if ($i < $totalSteps)
                            <div class="absolute top-5 left-10 w-full h-0.5 {{ $currentStep > $i ? 'bg-blue-200 dark:bg-blue-800' : 'bg-gray-200 dark:bg-gray-700' }}"></div>
                        @endif
                    </li>
                @endfor
            </ol>
        </nav>
    </div>

    <!-- Flash Messages -->
    @if (session()->has('error'))
        <div class="mb-6">
            <flux:callout variant="danger" icon="exclamation-triangle">
                {{ session('error') }}
            </flux:callout>
        </div>
    @endif

    @if (session()->has('success'))
        <div class="mb-6">
            <flux:callout variant="success" icon="check-circle">
                {{ session('success') }}
            </flux:callout>
        </div>
    @endif

    <!-- Step Content -->
    <form wire:submit.prevent="completeSetup">
        @switch($currentStep)
            @case(1)
                @include('livewire.setup.steps.company-info')
                @break
            @case(2)
                @include('livewire.setup.steps.email-config')
                @break
            @case(3)
                @include('livewire.setup.steps.system-prefs')
                @break
            @case(4)
                @include('livewire.setup.steps.msp-settings')
                @break
            @case(5)
                @include('livewire.setup.steps.admin-user')
                @break
        @endswitch

        <!-- Navigation Buttons -->
        <div class="flex justify-between mt-8">
            @if($currentStep === 1)
                <button type="button" disabled class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-400 bg-gray-100 cursor-not-allowed">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Previous
                </button>
            @else
                <button type="button" wire:click="previousStep" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Previous
                </button>
            @endif

            @if ($currentStep < $totalSteps)
                <button type="button" wire:click="testClick" class="bg-green-500 text-white px-4 py-2 rounded mr-2">
                    TEST CLICK
                </button>
                <button type="button" wire:click="nextStep" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Next
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                </button>
            @else
                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    Complete Setup
                </button>
            @endif
        </div>
    </form>

    <!-- Ready to Complete Setup Notice -->
    @if ($currentStep === $totalSteps)
        <div class="mt-8">
            <flux:callout variant="info" icon="information-circle">
                <p class="font-medium">Ready to Complete Setup</p>
                <p class="mt-1">Your MSP ERP system will be initialized with all the settings you've configured. The administrator account will have full access to all system features.</p>
                <p class="mt-2 text-sm">Need help? Contact support at <a href="mailto:support@nestogy.com" class="text-blue-600 dark:text-blue-400 hover:underline">support@nestogy.com</a></p>
            </flux:callout>
        </div>
    @endif
</div>