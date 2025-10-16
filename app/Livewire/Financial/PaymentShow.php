<?php

namespace App\Livewire\Financial;

use App\Domains\Financial\Models\Payment;
use Livewire\Attributes\Locked;
use Livewire\Component;

class PaymentShow extends Component
{
    #[Locked]
    public $paymentId;

    public string $activeTab = 'details';

    public function mount(Payment $payment)
    {
        $this->paymentId = $payment->id;
    }

    public function getPaymentProperty()
    {
        return Payment::with(['client', 'processedBy', 'applications.applicable', 'applications.appliedBy'])
            ->findOrFail($this->paymentId);
    }

    public function render()
    {
        return view('livewire.financial.payment-show');
    }
}
