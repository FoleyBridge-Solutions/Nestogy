<?php

namespace Tests\Unit\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Financial\Models\Invoice;
use App\Policies\InvoicePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePolicyTest extends TestCase
{
    use RefreshDatabase;

    protected InvoicePolicy $policy;
    protected Company $company;
    protected User $user;
    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new InvoicePolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->invoice = Invoice::factory()->create([
            'company_id' => $this->company->id,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_invoices_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Invoice::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_cannot_view_any_invoices_without_permission(): void
    {
        $this->assertFalse($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_invoice_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->invoice);

        $this->assertTrue($this->policy->view($this->user, $this->invoice));
    }

    public function test_user_cannot_view_invoice_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherInvoice));
    }

    public function test_user_can_create_invoice_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Invoice::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_invoice_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->invoice);

        $this->assertTrue($this->policy->update($this->user, $this->invoice));
    }

    public function test_user_cannot_update_invoice_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->update($this->user, $otherInvoice));
    }

    public function test_user_can_delete_invoice_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->invoice);

        $this->assertTrue($this->policy->delete($this->user, $this->invoice));
    }

    public function test_user_cannot_delete_invoice_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherInvoice = Invoice::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->delete($this->user, $otherInvoice));
    }
}
