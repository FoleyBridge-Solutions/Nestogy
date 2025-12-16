<?php

namespace App\Livewire\Portal;

use App\Domains\Financial\Models\PaymentMethod;
use App\Domains\Financial\Services\StripeGatewayService;
use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('client-portal.layouts.app')]
class PaymentMethods extends Component
{
    #[Computed]
    public function contact()
    {
        return Auth::guard('client')->user();
    }

    #[Computed]
    public function paymentMethods()
    {
        return PaymentMethod::where('client_id', $this->contact->client_id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function setDefault($paymentMethodId)
    {
        $paymentMethod = PaymentMethod::where('client_id', $this->contact->client_id)
            ->findOrFail($paymentMethodId);

        // Remove default from all other payment methods
        PaymentMethod::where('client_id', $this->contact->client_id)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this one as default
        $paymentMethod->update(['is_default' => true]);

        Flux::toast('Default payment method updated.', variant: 'success');
    }

    public function remove($paymentMethodId)
    {
        $paymentMethod = PaymentMethod::where('client_id', $this->contact->client_id)
            ->findOrFail($paymentMethodId);

        // Detach from Stripe if applicable
        if ($paymentMethod->provider === 'stripe' && $paymentMethod->provider_payment_method_id) {
            $stripeGateway = app(StripeGatewayService::class);
            $stripeGateway->detachPaymentMethod($paymentMethod->provider_payment_method_id);
        }

        $paymentMethod->delete();

        Flux::toast('Payment method removed.', variant: 'success');
    }

    public function render()
    {
        return view('livewire.portal.payment-methods');
    }
}
