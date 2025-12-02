<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Platform\Services\PlatformBillingService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class CompanyList extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all'; // all, active, suspended
    public string $subscriptionFilter = 'all'; // all, active, trialing, past_due, canceled

    public bool $showSuspendModal = false;
    public ?int $selectedCompanyId = null;
    public string $suspensionReason = '';

    protected PlatformBillingService $billingService;

    public function boot(PlatformBillingService $billingService): void
    {
        $this->billingService = $billingService;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingSubscriptionFilter(): void
    {
        $this->resetPage();
    }

    public function openSuspendModal(int $companyId): void
    {
        $this->selectedCompanyId = $companyId;
        $this->suspensionReason = '';
        $this->showSuspendModal = true;
    }

    public function closeSuspendModal(): void
    {
        $this->showSuspendModal = false;
        $this->selectedCompanyId = null;
        $this->suspensionReason = '';
    }

    public function suspendCompany(): void
    {
        $this->validate([
            'suspensionReason' => 'required|string|min:10|max:500',
        ]);

        $company = Company::findOrFail($this->selectedCompanyId);

        try {
            $this->billingService->suspendTenant($company, $this->suspensionReason);
            
            session()->flash('success', "Company '{$company->name}' has been suspended successfully.");
            $this->closeSuspendModal();
            
        } catch (\Exception $e) {
            session()->flash('error', "Failed to suspend company: {$e->getMessage()}");
        }
    }

    public function resumeCompany(int $companyId): void
    {
        $company = Company::findOrFail($companyId);

        try {
            $this->billingService->resumeTenant($company);
            
            session()->flash('success', "Company '{$company->name}' has been resumed successfully.");
            
        } catch (\Exception $e) {
            session()->flash('error', "Failed to resume company: {$e->getMessage()}");
        }
    }

    public function render()
    {
        $query = Company::where('id', '>', 1) // Exclude platform company
            ->with(['subscription']);

        // Search filter
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('email', 'like', "%{$this->search}%");
            });
        }

        // Status filter
        if ($this->statusFilter === 'active') {
            $query->where('is_active', true);
        } elseif ($this->statusFilter === 'suspended') {
            $query->where('is_active', false);
        }

        // Subscription filter
        if ($this->subscriptionFilter !== 'all') {
            $query->whereHas('subscription', function ($q) {
                $q->where('status', $this->subscriptionFilter);
            });
        }

        $companies = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('livewire.admin.company-list', [
            'companies' => $companies,
        ]);
    }
}
