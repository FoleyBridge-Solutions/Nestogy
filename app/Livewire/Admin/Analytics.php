<?php

namespace App\Livewire\Admin;

use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Analytics extends Component
{
    public array $cohortAnalysis = [];
    public array $stats = [];
    public array $topCompanies = [];

    protected PlatformBillingService $billingService;

    public function boot(PlatformBillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function mount(): void
    {
        $this->cohortAnalysis = $this->billingService->getCohortAnalysis(12);
        $this->stats = $this->billingService->getPlatformStats();
        $this->topCompanies = $this->billingService->getTopRevenueCompanies(10);
    }

    public function render()
    {
        return view('livewire.admin.analytics');
    }
}
