<?php

namespace Tests\Unit\Traits;

use App\Models\Company;
use App\Models\User;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SimpleBelongsToCompanyTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_can_access_companies_table()
    {
        // Test that we can access the companies table
        $companyCount = Company::count();
        $this->assertIsInt($companyCount);
        $this->assertGreaterThanOrEqual(0, $companyCount);
    }

    /** @test */
    public function it_can_create_test_companies()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $this->assertNotNull($company->id);
        $this->assertEquals('Test Company', $company->name);
    }

    /** @test */
    public function it_can_create_test_users()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@user.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertNotNull($user->id);
        $this->assertEquals($company->id, $user->company_id);
    }

    /** @test */
    public function it_has_belongs_to_company_trait_available()
    {
        $this->assertTrue(trait_exists(BelongsToCompany::class));
    }

    /** @test */
    public function user_authentication_works_in_tests()
    {
        $company = Company::create([
            'name' => 'Test Company',
            'email' => 'test@company.com',
        ]);

        $user = User::create([
            'company_id' => $company->id,
            'name' => 'Test User',
            'email' => 'test@user.com',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($user);
        
        $this->assertTrue(auth()->check());
        $this->assertEquals($user->id, auth()->id());
        $this->assertEquals($company->id, auth()->user()->company_id);
    }
}