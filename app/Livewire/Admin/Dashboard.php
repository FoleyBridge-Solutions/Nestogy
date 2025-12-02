<?php

namespace App\Livewire\Admin;

use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Dashboard extends Component
{
    public array $stats = [];
    public array $revenueTrends = [];
    public array $signupCancellationTrends = [];
    public array $topCompanies = [];

    protected PlatformBillingService $billingService;

    public function boot(PlatformBillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function mount(): void
    {
        $this->loadDashboardData();
    }

    public function loadDashboardData(): void
    {
        $this->stats = $this->billingService->getPlatformStats();
        $this->revenueTrends = $this->billingService->getRevenueTrends(12);
        $this->signupCancellationTrends = $this->billingService->getSignupCancellationTrends(12);
        $this->topCompanies = $this->billingService->getTopRevenueCompanies(5);
    }

    public function render()
    {
        return view('livewire.admin.dashboard');
    }
}
