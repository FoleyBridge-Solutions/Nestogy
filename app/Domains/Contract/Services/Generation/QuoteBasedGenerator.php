<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Financial\Models\Quote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class QuoteBasedGenerator implements ContractGenerationStrategy
{
    protected ContractDocumentBuilder $documentBuilder;
    protected ContractComplianceInitializer $complianceInitializer;

    public function __construct(
        ?ContractDocumentBuilder $documentBuilder = null,
        ?ContractComplianceInitializer $complianceInitializer = null
    ) {
        $this->documentBuilder = $documentBuilder ?: new ContractDocumentBuilder();
        $this->complianceInitializer = $complianceInitializer ?: new ContractComplianceInitializer();
    }

    public function generate(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $quote = $data['quote'];
            $template = $data['template'];
            $customizations = $data['customizations'] ?? [];

            Log::info('Starting contract generation from quote', [
                'quote_id' => $quote->id,
                'template_id' => $template->id,
                'user_id' => Auth::id(),
            ]);

            $this->validateQuoteEligibility($quote);

            $contractData = $this->buildContractData($quote, $template, $customizations);
            $contract = Contract::create($contractData);

            $this->setupSignatureRequirements($contract, $template);
            $this->documentBuilder->generate($contract);
            $this->complianceInitializer->initialize($contract, $quote);

            Log::info('Contract generated successfully from quote', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'quote_id' => $quote->id,
            ]);

            return $contract->fresh();
        });
    }

    protected function validateQuoteEligibility(Quote $quote): void
    {
        if ($quote->status === 'expired') {
            throw new \Exception('Cannot generate contract from expired quote');
        }

        if (!$quote->client_id) {
            throw new \Exception('Quote must have an associated client');
        }
    }

    protected function buildContractData(Quote $quote, ContractTemplate $template, array $customizations): array
    {
        return [
            'client_id' => $quote->client_id,
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
            'template_id' => $template->id,
            'quote_id' => $quote->id,
            'title' => $customizations['title'] ?? $this->generateContractTitle($quote, $template),
            'description' => $customizations['description'] ?? $quote->description,
            'status' => 'draft',
            'signature_status' => 'pending',
        ];
    }

    protected function generateContractTitle(Quote $quote, ContractTemplate $template): string
    {
        return sprintf(
            '%s - %s (%s)',
            $quote->client->name,
            $template->name,
            now()->format('Y-m-d')
        );
    }

    protected function setupSignatureRequirements(Contract $contract, ContractTemplate $template): void
    {
        if ($template->requires_signatures) {
            $contract->signatures()->create([
                'required_from' => 'client',
                'status' => 'pending',
            ]);
        }
    }
}
