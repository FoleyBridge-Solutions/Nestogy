<?php

namespace App\Domains\Contract\Requests;

use App\Domains\Contract\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContractStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $contract = $this->route('contract');
        return $this->user()->can('update', $contract);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                Rule::in(array_keys(Contract::getAvailableStatuses()))
            ],
            'reason' => 'sometimes|nullable|string|max:1000',
            'effective_date' => 'sometimes|nullable|date|after_or_equal:today',
            'notify_client' => 'sometimes|boolean',
            'send_notification' => 'sometimes|boolean',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status is required.',
            'status.in' => 'The selected status is invalid.',
            'reason.max' => 'Reason cannot exceed 1000 characters.',
            'effective_date.after_or_equal' => 'Effective date must be today or later.',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $contract = $this->route('contract');
            $newStatus = $this->status;
            $currentStatus = $contract->status;

            // Validate status transitions
            $this->validateStatusTransition($validator, $currentStatus, $newStatus);

            // Validate required reason for certain transitions
            $statusesRequiringReason = [
                Contract::STATUS_TERMINATED,
                Contract::STATUS_SUSPENDED,
                Contract::STATUS_CANCELLED,
            ];

            if (in_array($newStatus, $statusesRequiringReason) && empty($this->reason)) {
                $validator->errors()->add('reason', 'Reason is required when changing status to ' . $newStatus . '.');
            }

            // Validate signature requirements for activation
            if ($newStatus === Contract::STATUS_ACTIVE) {
                if ($contract->signature_status !== Contract::SIGNATURE_FULLY_EXECUTED) {
                    $validator->errors()->add('status', 'Contract must be fully signed before activation.');
                }
            }

            // Validate that terminated contracts cannot be reactivated
            if ($currentStatus === Contract::STATUS_TERMINATED && $newStatus === Contract::STATUS_ACTIVE) {
                $validator->errors()->add('status', 'Terminated contracts cannot be reactivated.');
            }

            // Validate that cancelled contracts cannot be changed to active
            if ($currentStatus === Contract::STATUS_CANCELLED && 
                in_array($newStatus, [Contract::STATUS_ACTIVE, Contract::STATUS_SIGNED])) {
                $validator->errors()->add('status', 'Cancelled contracts cannot be reactivated.');
            }

            // Validate effective date for future status changes
            if ($this->has('effective_date') && $this->effective_date) {
                $effectiveDate = \Carbon\Carbon::parse($this->effective_date);
                
                // Some status changes should be immediate
                $immediateStatuses = [
                    Contract::STATUS_SUSPENDED,
                    Contract::STATUS_CANCELLED,
                ];

                if (in_array($newStatus, $immediateStatuses) && $effectiveDate->isAfter(now())) {
                    $validator->errors()->add('effective_date', "Status change to {$newStatus} must be effective immediately.");
                }
            }
        });
    }

    /**
     * Validate status transition rules.
     */
    protected function validateStatusTransition($validator, string $currentStatus, string $newStatus): void
    {
        // Define allowed transitions
        $allowedTransitions = [
            Contract::STATUS_DRAFT => [
                Contract::STATUS_PENDING_REVIEW,
                Contract::STATUS_CANCELLED,
            ],
            Contract::STATUS_PENDING_REVIEW => [
                Contract::STATUS_DRAFT,
                Contract::STATUS_UNDER_NEGOTIATION,
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_CANCELLED,
            ],
            Contract::STATUS_UNDER_NEGOTIATION => [
                Contract::STATUS_PENDING_REVIEW,
                Contract::STATUS_PENDING_SIGNATURE,
                Contract::STATUS_CANCELLED,
            ],
            Contract::STATUS_PENDING_SIGNATURE => [
                Contract::STATUS_UNDER_NEGOTIATION,
                Contract::STATUS_SIGNED,
                Contract::STATUS_CANCELLED,
            ],
            Contract::STATUS_SIGNED => [
                Contract::STATUS_ACTIVE,
                Contract::STATUS_CANCELLED,
                Contract::STATUS_TERMINATED,
            ],
            Contract::STATUS_ACTIVE => [
                Contract::STATUS_SUSPENDED,
                Contract::STATUS_TERMINATED,
                Contract::STATUS_EXPIRED,
            ],
            Contract::STATUS_SUSPENDED => [
                Contract::STATUS_ACTIVE,
                Contract::STATUS_TERMINATED,
            ],
            Contract::STATUS_TERMINATED => [
                // Terminated is final - no transitions allowed
            ],
            Contract::STATUS_EXPIRED => [
                Contract::STATUS_TERMINATED,
                // Could allow renewal to active in future
            ],
            Contract::STATUS_CANCELLED => [
                // Cancelled is final - no transitions allowed
            ],
        ];

        // Allow staying in same status (for updates with same status)
        if ($currentStatus === $newStatus) {
            return;
        }

        // Check if transition is allowed
        $allowed = $allowedTransitions[$currentStatus] ?? [];
        
        if (!in_array($newStatus, $allowed)) {
            $validator->errors()->add('status', "Cannot change status from {$currentStatus} to {$newStatus}.");
        }
    }

    /**
     * Get validated data with additional processing.
     */
    public function getProcessedData(): array
    {
        $validated = $this->validated();

        // Set default values
        $validated['notify_client'] = $validated['notify_client'] ?? false;
        $validated['send_notification'] = $validated['send_notification'] ?? true;
        $validated['effective_date'] = $validated['effective_date'] ?? now()->toDateString();

        // Add metadata
        $validated['status_changed_by'] = $this->user()->id;
        $validated['status_changed_at'] = now();
        $validated['previous_status'] = $this->route('contract')->status;

        return $validated;
    }
}