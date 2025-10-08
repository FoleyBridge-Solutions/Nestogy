<?php

namespace App\Domains\Financial\Services;

use App\Models\TaxExemption;
use App\Models\TaxExemptionUsage;

class VoIPTaxExemptionService
{
    protected ?int $companyId = null;

    public function setCompanyId(int $companyId): self
    {
        $this->companyId = $companyId;

        return $this;
    }

    public function getClientExemptions(?int $clientId, $jurisdictions): \Illuminate\Database\Eloquent\Collection
    {
        if (! $clientId) {
            return collect();
        }

        $jurisdictionIds = $jurisdictions->pluck('id')->toArray();

        return TaxExemption::where('company_id', $this->companyId)
            ->where('client_id', $clientId)
            ->valid()
            ->where(function ($query) use ($jurisdictionIds) {
                $query->where('is_blanket_exemption', true)
                    ->orWhereIn('tax_jurisdiction_id', $jurisdictionIds);
            })
            ->orderBy('priority')
            ->get();
    }

    public function applyExemptions(array $taxes, $exemptions, string $taxLevel): array
    {
        if ($exemptions->isEmpty()) {
            return $taxes;
        }

        $exemptedTaxes = [];
        $exemptionsApplied = [];

        foreach ($taxes as $tax) {
            $originalTaxAmount = $tax['tax_amount'];
            $exemptedAmount = 0.0;

            foreach ($exemptions as $exemption) {
                if ($exemption->appliesToTaxType($tax['tax_type'])) {
                    $exemptionAmount = $exemption->calculateExemptionAmount($tax['tax_amount'], [
                        'service_type' => $tax['service_type'] ?? null,
                        'amount' => $tax['base_amount'],
                    ]);

                    if ($exemptionAmount > 0) {
                        $exemptedAmount += $exemptionAmount;
                        $exemptionsApplied[] = [
                            'exemption_id' => $exemption->id,
                            'exemption_name' => $exemption->exemption_name,
                            'tax_name' => $tax['tax_name'],
                            'original_amount' => $originalTaxAmount,
                            'exempted_amount' => $exemptionAmount,
                        ];
                    }
                }
            }

            $tax['tax_amount'] = max(0, $originalTaxAmount - $exemptedAmount);
            $tax['exempted_amount'] = min($exemptedAmount, $originalTaxAmount);

            $exemptedTaxes[] = $tax;
        }

        return $exemptedTaxes;
    }

    public function recordExemptionUsage(array $exemptionsApplied, ?int $invoiceId = null, ?int $quoteId = null): void
    {
        foreach ($exemptionsApplied as $exemptionData) {
            TaxExemptionUsage::create([
                'company_id' => $this->companyId,
                'tax_exemption_id' => $exemptionData['exemption_id'],
                'invoice_id' => $invoiceId,
                'quote_id' => $quoteId,
                'original_tax_amount' => $exemptionData['original_amount'],
                'exempted_amount' => $exemptionData['exempted_amount'],
                'final_tax_amount' => $exemptionData['original_amount'] - $exemptionData['exempted_amount'],
                'exemption_reason' => $exemptionData['exemption_name'],
                'used_at' => now(),
            ]);
        }
    }
}
