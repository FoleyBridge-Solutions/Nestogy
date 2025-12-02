<?php

namespace Tests\Unit\Policies;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Product\Models\Product;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected ProductPolicy $policy;
    protected Company $company;
    protected User $user;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new ProductPolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->product = Product::factory()->create([
            'company_id' => $this->company->id,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_products_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Product::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_product_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->product);

        $this->assertTrue($this->policy->view($this->user, $this->product));
    }

    public function test_user_cannot_view_product_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherProduct = Product::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherProduct));
    }

    public function test_user_can_create_product_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Product::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_product_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->product);

        $this->assertTrue($this->policy->update($this->user, $this->product));
    }

    public function test_user_can_delete_product_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->product);

        $this->assertTrue($this->policy->delete($this->user, $this->product));
    }
}
