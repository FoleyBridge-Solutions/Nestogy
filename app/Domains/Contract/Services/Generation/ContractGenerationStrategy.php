<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;

interface ContractGenerationStrategy
{
    public function generate(array $data): Contract;
}
