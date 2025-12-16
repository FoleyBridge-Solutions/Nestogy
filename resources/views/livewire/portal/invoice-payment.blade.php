<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <flux:heading size="xl">Pay Invoice #{{ $invoice->number }}</flux:heading>
        <flux:subheading>Complete your payment securely</flux:subheading>
    </div>

    <flux:card class="space-y-6">
        <flux:separator />

        {{-- Invoice Summary --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <flux:heading size="sm" class="mb-2">Invoice Details</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Invoice Number:</span>
                        <span class="font-medium">#{{ $invoice->number }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Date:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($invoice->date)->format('M d, Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Due Date:</span>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') }}</span>
                    </div>
                </div>
            </div>

            <div>
                <flux:heading size="sm" class="mb-2">Payment Summary</flux:heading>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Invoice Total:</span>
                        <span class="font-medium">${{ number_format($invoice->amount, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-zinc-500">Amount Paid:</span>
                        <span class="font-medium">${{ number_format($invoice->amount - $invoice->getBalance(), 2) }}</span>
                    </div>
                    <flux:separator />
                    <div class="flex justify-between text-base">
                        <span class="font-semibold">Balance Due:</span>
                        <span class="font-bold text-lg">${{ number_format($invoice->getBalance(), 2) }}</span>
                    </div>
                </div>
            </div>
        </div>

        <flux:separator />

        <form wire:submit="processPayment">
            {{-- Payment Amount --}}
            <div class="space-y-6">
                <flux:field>
                    <flux:label>Payment Amount</flux:label>
                    <flux:input 
                        wire:model="amount" 
                        type="number" 
                        step="0.01" 
                        min="0.01"
                        :max="$invoice->getBalance()"
                        placeholder="0.00"
                        icon="currency-dollar"
                    />
                    <flux:error name="amount" />
                    <flux:description>
                        Maximum: ${{ number_format($invoice->getBalance(), 2) }}
                    </flux:description>
                </flux:field>

                {{-- Payment Method Selection --}}
                @if($this->savedPaymentMethods->count() > 0)
                    <div class="space-y-4">
                        <flux:heading size="sm">Payment Method</flux:heading>

                        {{-- Saved Payment Methods --}}
                        <div class="space-y-3">
                            @foreach($this->savedPaymentMethods as $method)
                                <flux:radio.group>
                                    <flux:radio 
                                        wire:model.live="payment_method_id" 
                                        value="{{ $method->id }}"
                                        label="{{ $method->getDisplayName() }}"
                                        description="{{ $method->is_default ? 'Default payment method' : '' }}"
                                    />
                                </flux:radio.group>
                            @endforeach

                            {{-- Use New Card Option --}}
                            <flux:radio.group>
                                <flux:radio 
                                    wire:model.live="use_new_card" 
                                    :value="true"
                                    label="Use a new card"
                                    description="Add a new payment method"
                                />
                            </flux:radio.group>
                        </div>
                    </div>
                @else
                    {{-- No saved payment methods - force use_new_card --}}
                    <flux:heading size="sm">Card Information</flux:heading>
                @endif

                {{-- Stripe Card Element (shown when using new card OR no saved methods) --}}
                @if($use_new_card || $this->savedPaymentMethods->count() === 0)
                    <div class="space-y-4">
                        <div 
                            id="card-element" 
                            class="p-4 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800"
                            wire:ignore
                        >
                            <!-- Stripe Card Element will be inserted here -->
                        </div>
                        <div id="card-errors" class="text-red-600 text-sm" role="alert"></div>

                        <flux:checkbox 
                            wire:model="save_payment_method" 
                            label="Save this card for future payments"
                        />
                    </div>
                @endif

                <flux:error name="payment_method_id" />
                <flux:error name="stripe_payment_method_id" />

                {{-- Security Notice --}}
                <flux:callout icon="lock-closed" variant="secondary">
                    <flux:callout.text>
                        Your payment information is encrypted and secure. We use industry-standard security measures to protect your data.
                    </flux:callout.text>
                </flux:callout>

                {{-- Submit Button --}}
                <div class="flex items-center justify-between pt-4">
                    <flux:button 
                        href="{{ route('client.invoices.show', $invoice) }}" 
                        variant="ghost"
                    >
                        Cancel
                    </flux:button>

                    <flux:button 
                        type="submit" 
                        variant="primary"
                        :disabled="$processing"
                    >
                        @if($processing)
                            <flux:icon.arrow-path class="animate-spin" variant="micro" />
                            Processing...
                        @else
                            Pay ${{ number_format((float)$amount, 2) }}
                        @endif
                    </flux:button>
                </div>
            </div>
        </form>
    </flux:card>

    @assets
    <script src="https://js.stripe.com/v3/"></script>
    @endassets

    @script
    <script>
        let stripe, cardElement;

        // Initialize Stripe when component loads
        stripe = Stripe('{{ $this->stripePublishableKey }}');
        const elements = stripe.elements();
        
        cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#1f2937',
                    fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
                    '::placeholder': {
                        color: '#9ca3af',
                    },
                },
            },
        });

        // Mount card element
        cardElement.mount('#card-element');
        
        // Handle real-time validation errors
        cardElement.on('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Listen for createStripeToken event
        $wire.on('createStripeToken', async () => {
            const {paymentMethod, error} = await stripe.createPaymentMethod({
                type: 'card',
                card: cardElement,
            });

            if (error) {
                document.getElementById('card-errors').textContent = error.message;
                $wire.set('processing', false);
            } else {
                $wire.set('stripe_payment_method_id', paymentMethod.id);
                $wire.call('submitPayment');
            }
        });
    </script>
    @endscript
</div>
