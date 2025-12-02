<?php

namespace App\Livewire\Admin;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class CompanyDetail extends Component
{
    public Company $company;
    public string $activeTab = 'overview';

    public function mount(Company $company): void
    {
        if ($company->id === 1) {
            abort(403, 'Cannot view platform company details');
        }

        $this->company = $company;
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        $subscription = $this->company->subscription;
        
        $adminUsers = User::where('company_id', $this->company->id)
            ->whereHas('roles', function ($query) {
                $query->where('name', 'admin');
            })
            ->get();

        $allUsers = User::where('company_id', $this->company->id)->get();

        return view('livewire.admin.company-detail', [
            'subscription' => $subscription,
            'adminUsers' => $adminUsers,
            'allUsers' => $allUsers,
        ]);
    }
}
