<?php

namespace App\Livewire\Portal;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSignature as ContractSignatureModel;
use App\Domains\Security\Services\DigitalSignatureService;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class ContractSignature extends Component
{
    public Contract $contract;

    public $showModal = false;

    public $signatureMethod = 'draw';

    public $typedName = '';

    public $signatureData = '';

    public $termsAccepted = false;

    public $errorMessage = '';

    public $successMessage = '';

    public $processing = false;

    protected function rules()
    {
        return [
            'signatureMethod' => 'required|in:draw,type',
            'typedName' => 'required_if:signatureMethod,type|min:2',
            'signatureData' => 'required_if:signatureMethod,draw',
            'termsAccepted' => 'required|accepted',
        ];
    }

    protected $messages = [
        'typedName.required_if' => 'Please enter your full name.',
        'typedName.min' => 'Name must be at least 2 characters.',
        'signatureData.required_if' => 'Please draw your signature.',
        'termsAccepted.accepted' => 'You must accept the terms and conditions.',
    ];

    public function mount(Contract $contract)
    {
        $this->contract = $contract;
    }

    public function openModal()
    {
        $this->showModal = true;
        $this->resetForm();
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function updatedSignatureMethod()
    {
        $this->errorMessage = '';
        $this->successMessage = '';
    }

    public function submitSignature(DigitalSignatureService $signatureService)
    {
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->processing = true;

        try {
            // Validate form
            $this->validate();

            // Determine signature data based on method
            $finalSignatureData = $this->signatureMethod === 'type'
                ? $this->typedName
                : $this->signatureData;

            if (empty($finalSignatureData)) {
                $this->errorMessage = $this->signatureMethod === 'type'
                    ? 'Please enter your name.'
                    : 'Please draw your signature.';
                $this->processing = false;

                return;
            }

            // Find pending signature for this client
            $signature = ContractSignatureModel::where('contract_id', $this->contract->id)
                ->where('signer_email', auth('client')->user()->email)
                ->where('status', 'pending')
                ->first();

            if (! $signature) {
                $this->errorMessage = 'No pending signature found for this contract.';
                $this->processing = false;

                return;
            }

            // Prepare signature data
            $signatureDataPayload = [
                'signature_method' => $this->signatureMethod,
                'signature_data' => $finalSignatureData,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'signed_at' => now(),
            ];

            // Process the signature
            $success = $signatureService->processClientSignature($signature, $signatureDataPayload);

            if ($success) {
                // Log activity
                $this->contract->auditLogs()->create([
                    'user_type' => 'client',
                    'user_id' => auth('client')->id(),
                    'action' => 'contract_signed',
                    'description' => 'Contract signed by client',
                    'changes' => $signatureDataPayload,
                    'company_id' => $this->contract->company_id,
                ]);

                $this->successMessage = 'Contract signed successfully!';

                // Redirect after short delay
                $this->dispatch('signature-success');

                return redirect()->route('client.contracts.show', $this->contract->id);
            } else {
                $this->errorMessage = 'Failed to process signature. Please try again.';
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->errorMessage = collect($e->errors())->flatten()->first();
        } catch (\Exception $e) {
            Log::error('Error signing contract in Livewire', [
                'contract_id' => $this->contract->id,
                'client_id' => auth('client')->id(),
                'error' => $e->getMessage(),
            ]);

            $this->errorMessage = 'An error occurred while signing the contract. Please try again.';
        } finally {
            $this->processing = false;
        }
    }

    public function resetForm()
    {
        $this->signatureMethod = 'draw';
        $this->typedName = '';
        $this->signatureData = '';
        $this->termsAccepted = false;
        $this->errorMessage = '';
        $this->successMessage = '';
        $this->processing = false;
    }

    public function render()
    {
        $pendingSignature = ContractSignatureModel::where('contract_id', $this->contract->id)
            ->where('signer_email', auth('client')->user()->email)
            ->where('status', 'pending')
            ->first();

        return view('livewire.portal.contract-signature', [
            'hasPendingSignature' => ! is_null($pendingSignature),
        ]);
    }
}
