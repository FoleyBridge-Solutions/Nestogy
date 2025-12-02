<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\CompanySubscription;
use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class BillingDashboard extends Component
{
    public array $stats = [];
    public array $failedPayments = [];

    protected PlatformBillingService $billingService;

    public function boot(PlatformBillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function mount(): void
    {
        $this->stats = $this->billingService->getPlatformStats();
        $this->failedPayments = $this->billingService->getFailedPaymentSubscriptions();
    }

    public function render()
    {
        $activeSubscriptions = CompanySubscription::where('status', CompanySubscription::STATUS_ACTIVE)
            ->with('company')
            ->latest()
            ->take(10)
            ->get();

        return view('livewire.admin.billing-dashboard', [
            'activeSubscriptions' => $activeSubscriptions,
        ]);
    }
}
