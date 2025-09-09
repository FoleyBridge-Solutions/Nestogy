<?php

namespace Tests\Unit\Models;

use App\Models\Contract;
use App\Models\Client;
use App\Models\ContractTemplate;
use App\Models\ContractMilestone;
use App\Models\ContractSignature;
use App\Models\ContractApproval;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ContractTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test user and company
        $this->user = \App\Models\User::factory()->create();
        $this->company = \App\Models\Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_can_create_a_contract()
    {
        $contractData = [
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_number' => 'CNT-2024-001',
            'title' => 'Test Service Agreement',
            'contract_type' => 'service_agreement',
            'status' => 'draft',
            'contract_value' => 10000.00,
            'start_date' => now(),
            'end_date' => now()->addMonths(12),
            'terms' => 'Standard service terms and conditions.',
            'created_by' => $this->user->id,
        ];

        $contract = Contract::create($contractData);

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertEquals($contractData['contract_number'], $contract->contract_number);
        $this->assertEquals($contractData['title'], $contract->title);
        $this->assertEquals($contractData['contract_value'], $contract->contract_value);
        $this->assertDatabaseHas('contracts', [
            'id' => $contract->id,
            'contract_number' => $contractData['contract_number'],
        ]);
    }

    /** @test */
    public function it_belongs_to_a_company()
    {
        $contract = Contract::factory()->create(['company_id' => $this->company->id]);

        $this->assertInstanceOf(\App\Models\Company::class, $contract->company);
        $this->assertEquals($this->company->id, $contract->company->id);
    }

    /** @test */
    public function it_belongs_to_a_client()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertInstanceOf(Client::class, $contract->client);
        $this->assertEquals($this->client->id, $contract->client->id);
    }

    /** @test */
    public function it_can_have_milestones()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $milestone = ContractMilestone::factory()->create(['contract_id' => $contract->id]);

        $this->assertTrue($contract->milestones->contains($milestone));
        $this->assertInstanceOf(ContractMilestone::class, $contract->milestones->first());
    }

    /** @test */
    public function it_can_have_signatures()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $signature = ContractSignature::factory()->create(['contract_id' => $contract->id]);

        $this->assertTrue($contract->signatures->contains($signature));
        $this->assertInstanceOf(ContractSignature::class, $contract->signatures->first());
    }

    /** @test */
    public function it_can_calculate_completion_percentage()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        // Create milestones
        ContractMilestone::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'completed'
        ]);
        ContractMilestone::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'completed'
        ]);
        ContractMilestone::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'in_progress'
        ]);

        $this->assertEquals(66.67, round($contract->completion_percentage, 2));
    }

    /** @test */
    public function it_can_determine_if_active()
    {
        $activeContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active',
            'start_date' => now()->subDay(),
            'end_date' => now()->addMonth(),
        ]);

        $inactiveContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'completed',
        ]);

        $this->assertTrue($activeContract->isActive());
        $this->assertFalse($inactiveContract->isActive());
    }

    /** @test */
    public function it_can_determine_if_expired()
    {
        $expiredContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->subDay(),
        ]);

        $activeContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addMonth(),
        ]);

        $this->assertTrue($expiredContract->isExpired());
        $this->assertFalse($activeContract->isExpired());
    }

    /** @test */
    public function it_can_determine_if_expiring_soon()
    {
        $expiringSoonContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addDays(15),
        ]);

        $notExpiringSoonContract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addMonths(6),
        ]);

        $this->assertTrue($expiringSoonContract->isExpiringSoon());
        $this->assertFalse($notExpiringSoonContract->isExpiringSoon());
    }

    /** @test */
    public function it_can_check_if_fully_signed()
    {
        $contract = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        // No signatures
        $this->assertFalse($contract->isFullySigned());

        // Add pending signature
        ContractSignature::factory()->create([
            'contract_id' => $contract->id,
            'status' => 'pending'
        ]);
        $contract->refresh();
        $this->assertFalse($contract->isFullySigned());

        // Complete signature
        $contract->signatures()->update(['status' => 'signed']);
        $contract->refresh();
        $this->assertTrue($contract->isFullySigned());
    }

    /** @test */
    public function it_can_generate_unique_contract_number()
    {
        $contract1 = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $contract2 = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
        ]);

        $this->assertNotEquals($contract1->contract_number, $contract2->contract_number);
        $this->assertStringStartsWith('CNT-', $contract1->contract_number);
        $this->assertStringStartsWith('CNT-', $contract2->contract_number);
    }

    /** @test */
    public function it_validates_required_fields()
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Contract::create([
            // Missing required fields
            'title' => 'Test Contract',
        ]);
    }

    /** @test */
    public function it_can_scope_by_status()
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'active'
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'status' => 'draft'
        ]);

        $activeContracts = Contract::active()->get();
        $draftContracts = Contract::draft()->get();

        $this->assertEquals(1, $activeContracts->count());
        $this->assertEquals(1, $draftContracts->count());
        $this->assertEquals('active', $activeContracts->first()->status);
        $this->assertEquals('draft', $draftContracts->first()->status);
    }

    /** @test */
    public function it_can_scope_expiring_soon()
    {
        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addDays(15)
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'end_date' => now()->addMonths(6)
        ]);

        $expiringSoon = Contract::expiringSoon()->get();

        $this->assertEquals(1, $expiringSoon->count());
    }

    /** @test */
    public function it_can_filter_by_date_range()
    {
        $startDate = now()->subMonth();
        $endDate = now()->addMonth();

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_at' => now()
        ]);

        Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'created_at' => now()->subMonths(2)
        ]);

        $contractsInRange = Contract::createdBetween($startDate, $endDate)->get();

        $this->assertEquals(1, $contractsInRange->count());
    }

    /** @test */
    public function it_can_calculate_total_value()
    {
        $contract1 = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_value' => 10000.00
        ]);

        $contract2 = Contract::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'contract_value' => 15000.00
        ]);

        $totalValue = Contract::where('company_id', $this->company->id)->sum('contract_value');

        $this->assertEquals(25000.00, $totalValue);
    }
}