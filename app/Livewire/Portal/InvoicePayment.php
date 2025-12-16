<?php

namespace App\Livewire\Portal;

use App\Domains\Financial\Models\Invoice;
use App\Domains\Financial\Models\PaymentMethod;
use App\Domains\Financial\Services\PortalPaymentService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('client-portal.layouts.app')]
class InvoicePayment extends Component
{
    public Invoice $invoice;

    public $amount = '';

    public $payment_method_id = '';

    public $save_payment_method = false;

    public $use_new_card = false;

    public $stripe_payment_method_id = '';

    public $processing = false;

    protected function rules()
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'payment_method_id' => 'required_unless:use_new_card,true',
            'stripe_payment_method_id' => 'required_if:use_new_card,true',
        ];
    }

    protected $messages = [
        'amount.required' => 'Please enter a payment amount.',
        'amount.min' => 'Amount must be greater than zero.',
        'payment_method_id.required_unless' => 'Please select a payment method.',
        'stripe_payment_method_id.required_if' => 'Payment method is required.',
    ];

    public function mount(Invoice $invoice)
    {
        $this->invoice = $invoice;
        $this->amount = number_format($invoice->getBalance(), 2, '.', '');

        // Check if client has saved payment methods
        $savedMethods = $this->client->paymentMethods()
            ->where('is_active', true)
            ->where('verified', true)
            ->get();

        if ($savedMethods->count() === 0) {
            // No saved methods, force new card entry
            $this->use_new_card = true;
        } else {
            // Pre-select default payment method if available
            $defaultMethod = $savedMethods->where('is_default', true)->first();

            if ($defaultMethod) {
                $this->payment_method_id = $defaultMethod->id;
            }
        }
    }

    #[Computed]
    public function client()
    {
        return $this->invoice->client;
    }

    #[Computed]
    public function contact()
    {
        return Auth::guard('client')->user();
    }

    #[Computed]
    public function savedPaymentMethods()
    {
        return $this->client->paymentMethods()
            ->where('is_active', true)
            ->where('verified', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    #[Computed]
    public function stripePublishableKey()
    {
        return config('services.stripe.key');
    }

    public function updatedUseNewCard($value)
    {
        if ($value) {
            $this->payment_method_id = '';
        } else {
            $this->stripe_payment_method_id = '';
        }
    }

    public function processPayment()
    {
        $this->validate();

        $this->processing = true;

        // If using new card but no stripe token yet, dispatch event to create it
        if ($this->use_new_card && empty($this->stripe_payment_method_id)) {
            $this->dispatch('createStripeToken');
            return;
        }

        $this->submitPayment();
    }

    public function submitPayment()
    {
        try {
            $paymentService = app(PortalPaymentService::class);

            // Get or create payment method
            if ($this->use_new_card) {
                // Create new payment method from Stripe
                $result = $paymentService->addPaymentMethod($this->client, [
                    'type' => PaymentMethod::TYPE_CREDIT_CARD,
                    'provider' => 'stripe',
                    'stripe_payment_method_id' => $this->stripe_payment_method_id,
                    'is_default' => $this->save_payment_method,
                ]);

                if (!$result['success']) {
                    Flux::toast($result['message'], variant: 'danger');
                    $this->processing = false;
                    return;
                }

                $paymentMethod = PaymentMethod::find($result['payment_method_id']);
            } else {
                $paymentMethod = PaymentMethod::find($this->payment_method_id);

                if (!$paymentMethod || $paymentMethod->client_id !== $this->client->id) {
                    Flux::toast('Invalid payment method selected.', variant: 'danger');
                    $this->processing = false;
                    return;
                }
            }

            // Process payment
            $result = $paymentService->processPayment(
                $this->client,
                $this->invoice,
                $paymentMethod,
                [
                    'amount' => (float) $this->amount,
                    'currency' => $this->invoice->currency_code ?? 'USD',
                ]
            );

            if ($result['success']) {
                Flux::toast('Payment processed successfully!', variant: 'success');
                
                // Redirect to payment confirmation page
                return redirect()->route('portal.payments.confirmation', [
                    'payment' => $result['payment_id'],
                ]);
            } else {
                Flux::toast($result['message'] ?? 'Payment failed. Please try again.', variant: 'danger');
                $this->processing = false;
            }

        } catch (\Exception $e) {
            Log::error('Portal payment error', [
                'invoice_id' => $this->invoice->id,
                'client_id' => $this->client->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            Flux::toast('An error occurred processing your payment. Please try again.', variant: 'danger');
            $this->processing = false;
        }
    }

    public function render()
    {
        return view('livewire.portal.invoice-payment');
    }
}
