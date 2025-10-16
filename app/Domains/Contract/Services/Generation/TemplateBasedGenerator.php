<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Client\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class TemplateBasedGenerator implements ContractGenerationStrategy
{
    protected ContractDocumentBuilder $documentBuilder;

    public function __construct(?ContractDocumentBuilder $documentBuilder = null)
    {
        $this->documentBuilder = $documentBuilder ?: new ContractDocumentBuilder();
    }

    public function generate(array $data): Contract
    {
        return DB::transaction(function () use ($data) {
            $client = $data['client'];
            $template = $data['template'];
            $contractData = $data['data'];

            Log::info('Starting contract generation from template', [
                'client_id' => $client->id,
                'template_id' => $template->id,
                'user_id' => Auth::id(),
            ]);

            $processedData = $this->buildContractData($client, $template, $contractData);
            $contract = Contract::create($processedData);

            $this->applyTemplateConfigurations($contract, $template);
            $this->setupSignatureRequirements($contract, $template);
            $this->documentBuilder->generate($contract);

            Log::info('Contract generated from template', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);

            return $contract->fresh();
        });
    }

    protected function buildContractData(Client $client, ContractTemplate $template, array $contractData): array
    {
        return [
            'client_id' => $client->id,
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
            'template_id' => $template->id,
            'title' => $contractData['title'] ?? $template->name,
            'description' => $contractData['description'] ?? '',
            'status' => 'draft',
            'signature_status' => 'pending',
            ...array_intersect_key($contractData, array_flip(['start_date', 'end_date', 'value'])),
        ];
    }

    protected function applyTemplateConfigurations(Contract $contract, ContractTemplate $template): void
    {
        if ($template->default_terms) {
            $contract->update(['terms' => $template->default_terms]);
        }

        if ($template->default_conditions) {
            $contract->update(['conditions' => $template->default_conditions]);
        }
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
