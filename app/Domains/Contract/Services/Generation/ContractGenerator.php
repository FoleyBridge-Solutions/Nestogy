<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractTemplate;
use App\Domains\Client\Models\Client;
use App\Domains\Financial\Models\Quote;

class ContractGenerator
{
    protected QuoteBasedGenerator $quoteBasedGenerator;
    protected TemplateBasedGenerator $templateBasedGenerator;
    protected CustomContractGenerator $customContractGenerator;
    protected ContractRegenerator $contractRegenerator;

    public function __construct(
        ?QuoteBasedGenerator $quoteBasedGenerator = null,
        ?TemplateBasedGenerator $templateBasedGenerator = null,
        ?CustomContractGenerator $customContractGenerator = null,
        ?ContractRegenerator $contractRegenerator = null
    ) {
        $this->quoteBasedGenerator = $quoteBasedGenerator ?: new QuoteBasedGenerator();
        $this->templateBasedGenerator = $templateBasedGenerator ?: new TemplateBasedGenerator();
        $this->customContractGenerator = $customContractGenerator ?: new CustomContractGenerator();
        $this->contractRegenerator = $contractRegenerator ?: new ContractRegenerator();
    }

    public function generateFromQuote(
        Quote $quote,
        ContractTemplate $template,
        array $customizations = []
    ): Contract {
        return $this->quoteBasedGenerator->generate([
            'quote' => $quote,
            'template' => $template,
            'customizations' => $customizations,
        ]);
    }

    public function generateFromTemplate(
        Client $client,
        ContractTemplate $template,
        array $contractData = []
    ): Contract {
        return $this->templateBasedGenerator->generate([
            'client' => $client,
            'template' => $template,
            'data' => $contractData,
        ]);
    }

    public function createCustomContract(array $contractData): Contract
    {
        return $this->customContractGenerator->generate($contractData);
    }

    public function regenerateContract(Contract $contract, array $changes = []): Contract
    {
        return $this->contractRegenerator->generate([
            'contract' => $contract,
            'changes' => $changes,
        ]);
    }
}
