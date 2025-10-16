<?php

namespace Tests\Feature\Livewire\Contracts;

use App\Domains\Contract\Models\Contract;
use App\Livewire\Contracts\ContractLanguageEditor;
use App\Domains\Client\Models\Client;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Tests\RefreshesDatabase;
use Tests\TestCase;
use Livewire\Livewire;

class ContractLanguageEditorTest extends TestCase
{
    use RefreshesDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        
        // Give user permission to edit contracts
        \Bouncer::allow($this->user)->to('contracts.edit');
        
        $this->actingAs($this->user);
    }

    public function test_renders_successfully()
    {
        // Create a contract for testing
        $client = Client::factory()->create(['company_id' => $this->company->id]);
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $client->id,
            'status' => 'draft', // Must be draft to be editable
            'created_by' => $this->user->id, // Prevent factory from creating a new user
        ]);

        Livewire::test(ContractLanguageEditor::class, ['contract' => $contract])
            ->assertStatus(200);
    }
}
