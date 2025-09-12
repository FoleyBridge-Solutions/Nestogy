<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Create Your Nestogy Account</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .step-progress {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .step {
            display: flex;
            align-items: center;
            margin: 0 1rem;
        }
        .step-circle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-right: 0.5rem;
        }
        .step-circle.active {
            background-color: #3B82F6;
            color: white;
        }
        .step-circle.completed {
            background-color: #10B981;
            color: white;
        }
        .step-circle.pending {
            background-color: #E5E7EB;
            color: #6B7280;
        }
        .form-section {
            display: none;
        }
        .form-section.active {
            display: block;
        }
        .plan-card {
            border: 2px solid #E5E7EB;
            border-radius: 8px;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
        }
        .plan-card:hover {
            border-color: #3B82F6;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        .plan-card.selected {
            border-color: #3B82F6;
            background-color: #EFF6FF;
        }
        .stripe-element {
            border: 1px solid #D1D5DB;
            border-radius: 0.375rem;
            padding: 0.75rem;
            margin-bottom: 1rem;
        }
        .stripe-element:focus {
            border-color: #3B82F6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center py-12 px-6 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <!-- Header -->
        <div class="text-center">
            <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                Create Your Account
            </h2>
            <p class="mt-2 text-sm text-gray-600">
                Start your 14-day free trial today
            </p>
        </div>

        <!-- Progress Steps -->
        <div class="step-progress">
            <div class="step">
                <div class="step-circle active" id="step-1-circle">1</div>
                <span class="text-sm">Company</span>
            </div>
            <div class="step">
                <div class="step-circle pending" id="step-2-circle">2</div>
                <span class="text-sm">Admin</span>
            </div>
            <div class="step">
                <div class="step-circle pending" id="step-3-circle">3</div>
                <span class="text-sm">Plan</span>
            </div>
            <div class="step">
                <div class="step-circle pending" id="step-4-circle">4</div>
                <span class="text-sm">Payment</span>
            </div>
        </div>

        <!-- Registration Form -->
        <form id="registrationForm" method="POST" action="{{ route('signup.submit') }}" class="mt-8 space-y-6">
            @csrf
            
            <!-- Step 1: Company Information -->
            <div id="step-1" class="form-section active">
                <div class="space-y-4">
                    <div>
                        <label for="company_name" class="block text-sm font-medium text-gray-700">
                            Company Name *
                        </label>
                        <input id="company_name" name="company_name" type="text" required autocomplete="organization"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="Your company name" value="{{ old('company_name') }}">
                    </div>
                    
                    <div>
                        <label for="company_email" class="block text-sm font-medium text-gray-700">
                            Company Email *
                        </label>
                        <input id="company_email" name="company_email" type="email" required autocomplete="email"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="info@yourcompany.com" value="{{ old('company_email') }}">
                    </div>
                    
                    <div>
                        <label for="company_phone" class="block text-sm font-medium text-gray-700">
                            Phone Number
                        </label>
                        <input id="company_phone" name="company_phone" type="tel" autocomplete="tel"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="(555) 123-4567" value="{{ old('company_phone') }}">
                    </div>
                    
                    <div>
                        <label for="company_website" class="block text-sm font-medium text-gray-700">
                            Website
                        </label>
                        <input id="company_website" name="company_website" type="url" autocomplete="url"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="https://yourcompany.com" value="{{ old('company_website') }}">
                    </div>
                </div>
                
                <div class="flex justify-end mt-6">
                    <button type="button" id="next-1" class="group relative w-full flex justify-center py-2 px-6 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Continue
                    </button>
                </div>
            </div>

            <!-- Step 2: Admin User Information -->
            <div id="step-2" class="form-section">
                <div class="space-y-4">
                    <div>
                        <label for="admin_name" class="block text-sm font-medium text-gray-700">
                            Your Full Name *
                        </label>
                        <input id="admin_name" name="admin_name" type="text" required autocomplete="name"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="John Doe" value="{{ old('admin_name') }}">
                    </div>
                    
                    <div>
                        <label for="admin_email" class="block text-sm font-medium text-gray-700">
                            Your Email Address *
                        </label>
                        <input id="admin_email" name="admin_email" type="email" required autocomplete="email"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="john@yourcompany.com" value="{{ old('admin_email') }}">
                    </div>
                    
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-gray-700">
                            Password *
                        </label>
                        <input id="admin_password" name="admin_password" type="password" required autocomplete="new-password"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="Your secure password">
                    </div>
                    
                    <div>
                        <label for="admin_password_confirmation" class="block text-sm font-medium text-gray-700">
                            Confirm Password *
                        </label>
                        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required autocomplete="new-password"
                               class="mt-1 appearance-none relative block w-full px-6 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                               placeholder="Confirm your password">
                    </div>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button type="button" id="prev-2" class="group relative flex justify-center py-2 px-6 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Back
                    </button>
                    <button type="button" id="next-2" class="group relative flex justify-center py-2 px-6 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Continue
                    </button>
                </div>
            </div>

            <!-- Step 3: Subscription Plan -->
            <div id="step-3" class="form-section">
                <div class="space-y-4">
                    <h3 class="text-lg font-medium text-gray-900 mb-6">Choose Your Plan</h3>
                    <div id="plans-container" class="space-y-4">
                        <!-- Plans will be loaded dynamically -->
                        <div class="text-center py-8">
                            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-indigo-600 mx-auto"></div>
                            <p class="mt-2 text-gray-500">Loading plans...</p>
                        </div>
                    </div>
                    <input type="hidden" name="subscription_plan_id" id="selected_plan_id" value="{{ old('subscription_plan_id') }}">
                </div>
                
                <div class="flex justify-between mt-6">
                    <button type="button" id="prev-3" class="group relative flex justify-center py-2 px-6 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Back
                    </button>
                    <button type="button" id="next-3" class="group relative flex justify-center py-2 px-6 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" disabled>
                        Continue
                    </button>
                </div>
            </div>

            <!-- Step 4: Payment Information -->
            <div id="step-4" class="form-section">
                <div class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded-md p-6 mb-6">
                        <div class="flex">
                            <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                            </svg>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-blue-800">
                                    14-Day Free Trial
                                </h3>
                                <div class="mt-2 text-sm text-blue-700">
                                    <p>You won't be charged until your trial ends. We'll authorize your payment method with a $1 charge that will be refunded immediately.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Credit Card Information
                        </label>
                        <div id="card-element" class="stripe-element">
                            <!-- Stripe Elements will create form elements here -->
                        </div>
                        <div id="card-errors" role="alert" class="text-red-600 text-sm mt-2"></div>
                        <input type="hidden" name="payment_method_id" id="payment_method_id">
                    </div>
                    
                    <div class="flex items-center">
                        <input id="terms_accepted" name="terms_accepted" type="checkbox" required class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                        <label for="terms_accepted" class="ml-2 block text-sm text-gray-900">
                            I agree to the <a href="#" class="text-indigo-600 hover:text-indigo-500">Terms of Service</a> and <a href="#" class="text-indigo-600 hover:text-indigo-500">Privacy Policy</a>
                        </label>
                    </div>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button type="button" id="prev-4" class="group relative flex justify-center py-2 px-6 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        Back
                    </button>
                    <button type="submit" id="submit-form" class="group relative flex justify-center py-2 px-6 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500" disabled>
                        <span id="submit-text">Start Free Trial</span>
                        <div id="submit-spinner" class="hidden ml-2">
                            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        </div>
                    </button>
                </div>
            </div>
        </form>

        <!-- Error Messages -->
        @if ($errors->any())
            <div class="mt-6 bg-red-50 border border-red-200 rounded-md p-6">
                <div class="flex">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-red-800">
                            Please fix the following errors:
                        </h3>
                        <div class="mt-2 text-sm text-red-700">
                            <ul class="list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Initialize Stripe
        const stripe = Stripe('{{ config('services.stripe.key') }}');
        const elements = stripe.elements();
        
        // Current step
        let currentStep = 1;
        const totalSteps = 4;
        
        // Form elements
        const form = document.getElementById('registrationForm');
        let cardElement;
        let selectedPlan = null;
        
        // Initialize the form
        document.addEventListener('DOMContentLoaded', function() {
            initializeForm();
            loadSubscriptionPlans();
            setupStripeElements();
        });
        
        function initializeForm() {
            // Navigation buttons
            setupStepNavigation();
            
            // Form validation
            setupFormValidation();
        }
        
        function setupStepNavigation() {
            // Next buttons
            document.getElementById('next-1').addEventListener('click', () => validateAndProceed(1));
            document.getElementById('next-2').addEventListener('click', () => validateAndProceed(2));
            document.getElementById('next-3').addEventListener('click', () => validateAndProceed(3));
            
            // Previous buttons
            document.getElementById('prev-2').addEventListener('click', () => goToStep(1));
            document.getElementById('prev-3').addEventListener('click', () => goToStep(2));
            document.getElementById('prev-4').addEventListener('click', () => goToStep(3));
        }
        
        function setupFormValidation() {
            form.addEventListener('submit', handleFormSubmit);
        }
        
        async function validateAndProceed(step) {
            const formData = new FormData(form);
            formData.append('step', step);
            
            
            try {
                const response = await fetch('{{ route('signup.validate-step') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (result.valid) {
                    goToStep(step + 1);
                } else {
                    displayValidationErrors(result.errors);
                }
            } catch (error) {
                console.error('Validation error:', error);
                alert('An error occurred during validation. Please try again.');
            }
        }
        
        function goToStep(step) {
            // Hide all steps
            for (let i = 1; i <= totalSteps; i++) {
                document.getElementById(`step-${i}`).classList.remove('active');
                const circle = document.getElementById(`step-${i}-circle`);
                circle.classList.remove('active', 'completed', 'pending');
                
                if (i < step) {
                    circle.classList.add('completed');
                } else if (i === step) {
                    circle.classList.add('active');
                } else {
                    circle.classList.add('pending');
                }
            }
            
            // Show current step
            document.getElementById(`step-${step}`).classList.add('active');
            currentStep = step;
            
            // Initialize step-specific functionality
            if (step === 4) {
                initializePaymentStep();
            }
        }
        
        function initializePaymentStep() {
            const infoBox = document.querySelector('#step-4 .bg-blue-50');
            if (!infoBox || !selectedPlan) return;
            
            // Customize the info box based on plan type
            if (selectedPlan.price_monthly == 0) {
                // Free plan - show identity verification message
                infoBox.innerHTML = `
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                Identity Verification Required
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>We'll authorize your payment method with a $1 charge for identity verification. This charge will be refunded immediately and you won't be charged for the Free plan.</p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                // Paid plan - show trial message
                infoBox.innerHTML = `
                    <div class="flex">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                        <div class="ml-3">
                            <h3 class="text-sm font-medium text-blue-800">
                                14-Day Free Trial
                            </h3>
                            <div class="mt-2 text-sm text-blue-700">
                                <p>You won't be charged until your trial ends. We'll authorize your payment method with a $1 charge that will be refunded immediately.</p>
                            </div>
                        </div>
                    </div>
                `;
            }
        }
        
        async function loadSubscriptionPlans() {
            try {
                const response = await fetch('{{ route('signup.plans') }}');
                const data = await response.json();
                
                const container = document.getElementById('plans-container');
                container.innerHTML = '';
                
                window.availablePlans = data.plans; // Store globally
                data.plans.forEach(plan => {
                    const planCard = createPlanCard(plan);
                    container.appendChild(planCard);
                });
            } catch (error) {
                console.error('Error loading plans:', error);
                document.getElementById('plans-container').innerHTML = '<div class="text-center py-8 text-red-600">Failed to load plans. Please refresh the page.</div>';
            }
        }
        
        function createPlanCard(plan) {
            const card = document.createElement('div');
            card.className = 'plan-card';
            card.dataset.planId = plan.id;
            
            card.innerHTML = `
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="text-lg font-semibold text-gray-900">${plan.name}</h4>
                        <p class="text-sm text-gray-600 mt-1">${plan.description || ''}</p>
                        <div class="mt-2">
                            <span class="text-2xl font-bold text-gray-900">${plan.price_monthly == 0 ? 'Free' : plan.formatted_price}</span>
                            <span class="text-sm text-gray-500">${plan.price_monthly == 0 ? 'Forever' : '/month'}</span>
                        </div>
                        <p class="text-sm text-gray-500 mt-1">${plan.user_limit_text}</p>
                    </div>
                    <div class="flex-shrink-0">
                        <input type="radio" name="plan_selection" value="${plan.id}" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300">
                    </div>
                </div>
            `;
            
            card.addEventListener('click', () => selectPlan(plan.id, card));
            
            return card;
        }
        
        function selectPlan(planId, cardElement) {
            // Remove selection from all cards
            document.querySelectorAll('.plan-card').forEach(card => {
                card.classList.remove('selected');
                card.querySelector('input[type="radio"]').checked = false;
            });
            
            // Select this card
            cardElement.classList.add('selected');
            cardElement.querySelector('input[type="radio"]').checked = true;
            document.getElementById('selected_plan_id').value = planId;
            
            // Store selected plan data
            selectedPlan = window.availablePlans?.find(plan => plan.id == planId) || null;
            
            // Enable next button
            document.getElementById('next-3').disabled = false;
        }
        
        function setupStripeElements() {
            const style = {
                base: {
                    color: '#32325d',
                    fontFamily: '"Helvetica Neue", Helvetica, sans-serif',
                    fontSmoothing: 'antialiased',
                    fontSize: '16px',
                    '::placeholder': {
                        color: '#aab7c4'
                    }
                },
                invalid: {
                    color: '#fa755a',
                    iconColor: '#fa755a'
                }
            };
            
            cardElement = elements.create('card', { style: style });
            cardElement.mount('#card-element');
            
            cardElement.on('change', ({ error, complete }) => {
                const displayError = document.getElementById('card-errors');
                const submitButton = document.getElementById('submit-form');
                
                if (error) {
                    displayError.textContent = error.message;
                    submitButton.disabled = true;
                } else {
                    displayError.textContent = '';
                    submitButton.disabled = !complete || !document.getElementById('terms_accepted').checked;
                }
            });
            
            // Enable submit when terms are accepted
            document.getElementById('terms_accepted').addEventListener('change', function() {
                const submitButton = document.getElementById('submit-form');
                const cardComplete = document.getElementById('card-errors').textContent === '';
                submitButton.disabled = !this.checked || !cardComplete;
            });
        }
        
        async function handleFormSubmit(event) {
            event.preventDefault();
            
            const submitButton = document.getElementById('submit-form');
            const submitText = document.getElementById('submit-text');
            const submitSpinner = document.getElementById('submit-spinner');
            
            // Disable submit button and show loading
            submitButton.disabled = true;
            submitText.textContent = 'Processing...';
            submitSpinner.classList.remove('hidden');
            
            try {
                // Create payment method with Stripe
                const { paymentMethod, error } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement,
                    billing_details: {
                        name: document.getElementById('admin_name').value,
                        email: document.getElementById('admin_email').value,
                    },
                });
                
                if (error) {
                    throw new Error(error.message);
                }
                
                // Add payment method to form
                document.getElementById('payment_method_id').value = paymentMethod.id;
                
                // Submit the form
                form.submit();
                
            } catch (error) {
                console.error('Payment processing error:', error);
                
                // Show error
                const errorElement = document.getElementById('card-errors');
                errorElement.textContent = error.message;
                
                // Re-enable submit button
                submitButton.disabled = false;
                submitText.textContent = 'Start Free Trial';
                submitSpinner.classList.add('hidden');
            }
        }
        
        function displayValidationErrors(errors) {
            // Clear previous errors
            document.querySelectorAll('.error-message').forEach(el => el.remove());
            
            // Display new errors
            Object.keys(errors).forEach(field => {
                const input = document.getElementById(field);
                if (input) {
                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'error-message text-red-600 text-sm mt-1';
                    errorDiv.textContent = errors[field][0];
                    input.parentNode.appendChild(errorDiv);
                }
            });
        }
    </script>
</body>
</html>
