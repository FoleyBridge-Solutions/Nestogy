<?php

namespace App\Livewire\Portal;

use App\Domains\Financial\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('client-portal.layouts.app')]
class PaymentHistory extends Component
{
    use WithPagination;

    public $search = '';

    public $status_filter = 'all';

    #[Computed]
    public function contact()
    {
        return Auth::guard('client')->user();
    }

    #[Computed]
    public function payments()
    {
        $query = Payment::where('client_id', $this->contact->client_id)
            ->with(['invoice'])
            ->orderBy('payment_date', 'desc');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('payment_reference', 'like', "%{$this->search}%")
                    ->orWhere('gateway_transaction_id', 'like', "%{$this->search}%");
            });
        }

        if ($this->status_filter !== 'all') {
            $query->where('status', $this->status_filter);
        }

        return $query->paginate(15);
    }

    public function render()
    {
        return view('livewire.portal.payment-history');
    }
}
