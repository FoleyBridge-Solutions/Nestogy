<?php

namespace App\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Traits\HasAutomaticAI;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class ContractShow extends Component
{
    use HasAutomaticAI;

    public Contract $contract;

    public $showEditModal = false;
    public $showDeleteModal = false;
    public $showSignatureModal = false;

    public function mount(Contract $contract)
    {
        $this->contract = $contract;
        
        $this->initializeAI($contract);
        
        $this->contract->load([
            'client',
            'createdBy',
            'approvedBy',
            'signedBy',
            'quote',
            'assets',
            'amendments',
            'auditLogs',
            'complianceChecks',
            'invoices',
            'recurringInvoices',
        ]);
    }

    public function approveContract()
    {
        if (!Auth::user()->can('approve', $this->contract)) {
            $this->dispatch('error', 'You are not authorized to approve this contract.');
            return;
        }

        $this->contract->update([
            'status' => 'approved',
            'approved_by' => Auth::id(),
        ]);

        $this->contract->refresh();
        $this->dispatch('success', 'Contract approved successfully.');
    }

    public function sendForSignature()
    {
        if (!Auth::user()->can('sendForSignature', $this->contract)) {
            $this->dispatch('error', 'You are not authorized to send this contract for signature.');
            return;
        }

        $this->contract->sendForSignature();
        $this->contract->refresh();
        $this->dispatch('success', 'Contract sent for signature.');
    }

    public function downloadPdf()
    {
        return response()->download(
            $this->contract->generatePdf(),
            "contract-{$this->contract->contract_number}.pdf"
        );
    }

    public function render()
    {
        return view('livewire.contracts.contract-show');
    }

    protected function getModel()
    {
        return $this->contract;
    }

    protected function getAIAnalysisType(): string
    {
        return 'contract';
    }
}
