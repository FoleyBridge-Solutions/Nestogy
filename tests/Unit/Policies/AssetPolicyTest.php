<?php

namespace Tests\Unit\Policies;

use App\Domains\Asset\Models\Asset;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Policies\AssetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected AssetPolicy $policy;
    protected Company $company;
    protected User $user;
    protected Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new AssetPolicy();
        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->asset = Asset::factory()->create([
            'company_id' => $this->company->id,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
    }

    public function test_user_can_view_any_assets_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', Asset::class);

        $this->assertTrue($this->policy->viewAny($this->user));
    }

    public function test_user_can_view_asset_in_same_company(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('view', $this->asset);

        $this->assertTrue($this->policy->view($this->user, $this->asset));
    }

    public function test_user_cannot_view_asset_in_different_company(): void
    {
        $otherCompany = Company::factory()->create();
        $otherAsset = Asset::factory()->create([
            'company_id' => $otherCompany->id,
        ]);

        $this->assertFalse($this->policy->view($this->user, $otherAsset));
    }

    public function test_user_can_create_asset_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('create', Asset::class);

        $this->assertTrue($this->policy->create($this->user));
    }

    public function test_user_can_update_asset_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('update', $this->asset);

        $this->assertTrue($this->policy->update($this->user, $this->asset));
    }

    public function test_user_can_delete_asset_with_permission(): void
    {
        \Silber\Bouncer\BouncerFacade::allow($this->user)->to('delete', $this->asset);

        $this->assertTrue($this->policy->delete($this->user, $this->asset));
    }
}
