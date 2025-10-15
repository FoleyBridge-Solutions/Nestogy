<?php

namespace App\Livewire\Financial;

use App\Models\Payment;
use Livewire\Component;
use Livewire\Attributes\Locked;

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
