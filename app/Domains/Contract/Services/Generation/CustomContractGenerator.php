<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CustomContractGenerator implements ContractGenerationStrategy
{
    protected ContractDocumentBuilder $documentBuilder;

    public function __construct(?ContractDocumentBuilder $documentBuilder = null)
    {
        $this->documentBuilder = $documentBuilder ?: new ContractDocumentBuilder();
    }

    public function generate(array $contractData): Contract
    {
        return DB::transaction(function () use ($contractData) {
            Log::info('Creating custom contract', [
                'user_id' => Auth::id(),
            ]);

            $processedData = $this->processContractData($contractData);
            $contract = Contract::create($processedData);

            if ($contractData['generate_document'] ?? true) {
                $this->documentBuilder->generate($contract);
            }

            Log::info('Custom contract created', [
                'contract_id' => $contract->id,
                'contract_number' => $contract->contract_number,
            ]);

            return $contract->fresh();
        });
    }

    protected function processContractData(array $contractData): array
    {
        return [
            'client_id' => $contractData['client_id'],
            'company_id' => Auth::user()->company_id,
            'created_by' => Auth::id(),
            'template_id' => $contractData['template_id'] ?? null,
            'title' => $contractData['title'],
            'description' => $contractData['description'] ?? '',
            'status' => $contractData['status'] ?? 'draft',
            'signature_status' => $contractData['signature_status'] ?? 'pending',
            'start_date' => $contractData['start_date'] ?? null,
            'end_date' => $contractData['end_date'] ?? null,
            'value' => $contractData['value'] ?? 0,
            'terms' => $contractData['terms'] ?? null,
            'conditions' => $contractData['conditions'] ?? null,
        ];
    }
}
