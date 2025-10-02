<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_company_with_factory(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(Company::class, $company);
        $this->assertDatabaseHas('companies', ['id' => $company->id]);
    }

    public function test_company_has_required_attributes(): void
    {
        $company = Company::factory()->create([
            'name' => 'Test Company LLC',
            'currency' => 'USD',
        ]);

        $this->assertEquals('Test Company LLC', $company->name);
        $this->assertEquals('USD', $company->currency);
    }

    public function test_company_has_many_users(): void
    {
        $company = Company::factory()->create();
        
        User::factory()->count(3)->create(['company_id' => $company->id]);

        $this->assertCount(3, $company->users);
        $this->assertInstanceOf(User::class, $company->users->first());
    }

    public function test_company_has_many_clients(): void
    {
        $company = Company::factory()->create();
        
        Client::factory()->count(2)->create(['company_id' => $company->id]);

        $this->assertCount(2, $company->clients);
    }

    public function test_company_has_fillable_attributes(): void
    {
        $fillable = (new Company)->getFillable();

        $expectedFillable = ['name', 'email', 'phone', 'address', 'currency'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_company_factory_inactive_state(): void
    {
        $company = Company::factory()->inactive()->create();

        $this->assertFalse($company->is_active);
    }

    public function test_company_factory_suspended_state(): void
    {
        $company = Company::factory()->suspended('Non-payment')->create();

        $this->assertFalse($company->is_active);
        $this->assertNotNull($company->suspended_at);
        $this->assertEquals('Non-payment', $company->suspension_reason);
    }

    public function test_company_factory_currency_state(): void
    {
        $company = Company::factory()->currency('EUR')->create();

        $this->assertEquals('EUR', $company->currency);
    }

    public function test_company_can_be_in_different_countries(): void
    {
        $usCompany = Company::factory()->inCountry('United States')->create();
        $ukCompany = Company::factory()->inCountry('United Kingdom')->create();

        $this->assertEquals('United States', $usCompany->country);
        $this->assertEquals('United Kingdom', $ukCompany->country);
    }

    public function test_company_has_timestamps(): void
    {
        $company = Company::factory()->create();

        $this->assertNotNull($company->created_at);
        $this->assertNotNull($company->updated_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $company->created_at);
    }

    public function test_company_email_can_be_unique(): void
    {
        $company1 = Company::factory()->create(['email' => 'company1@example.com']);
        $company2 = Company::factory()->create(['email' => 'company2@example.com']);

        $this->assertNotEquals($company1->email, $company2->email);
    }

    public function test_company_has_default_currency(): void
    {
        $company = Company::factory()->create();

        $this->assertNotNull($company->currency);
        $this->assertContains($company->currency, ['USD', 'EUR', 'GBP', 'CAD']);
    }

    public function test_company_has_locale_setting(): void
    {
        $company = Company::factory()->create();

        $this->assertEquals('en_US', $company->locale);
    }

    public function test_get_currency_symbol_returns_correct_symbol(): void
    {
        $usdCompany = Company::factory()->create(['currency' => 'USD']);
        $eurCompany = Company::factory()->create(['currency' => 'EUR']);
        $gbpCompany = Company::factory()->create(['currency' => 'GBP']);

        $this->assertEquals('$', $usdCompany->getCurrencySymbol());
        $this->assertEquals('€', $eurCompany->getCurrencySymbol());
        $this->assertEquals('£', $gbpCompany->getCurrencySymbol());
    }

    public function test_get_currency_name_returns_correct_name(): void
    {
        $usdCompany = Company::factory()->create(['currency' => 'USD']);
        
        $this->assertEquals('US Dollar', $usdCompany->getCurrencyName());
    }

    public function test_format_currency_formats_amount_with_symbol(): void
    {
        $company = Company::factory()->create(['currency' => 'USD']);
        
        $formatted = $company->formatCurrency(1234.56);
        
        $this->assertEquals('$1,234.56', $formatted);
    }

    public function test_has_logo_returns_true_when_logo_set(): void
    {
        $companyWithLogo = Company::factory()->create(['logo' => 'logo.png']);
        $companyWithoutLogo = Company::factory()->create(['logo' => null]);

        $this->assertTrue($companyWithLogo->hasLogo());
        $this->assertFalse($companyWithoutLogo->hasLogo());
    }

    public function test_get_full_address_combines_address_parts(): void
    {
        $company = Company::factory()->create([
            'address' => '123 Business Ave',
            'city' => 'New York',
            'state' => 'NY',
            'zip' => '10001',
            'country' => 'USA',
        ]);

        $fullAddress = $company->getFullAddress();

        $this->assertStringContainsString('123 Business Ave', $fullAddress);
        $this->assertStringContainsString('New York', $fullAddress);
        $this->assertStringContainsString('NY', $fullAddress);
    }

    public function test_has_complete_address_returns_true_when_all_fields_set(): void
    {
        $completeCompany = Company::factory()->create([
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip' => '62701',
        ]);

        $incompleteCompany = Company::factory()->create([
            'address' => '123 Main St',
            'city' => null,
            'state' => null,
            'zip' => null,
        ]);

        $this->assertTrue($completeCompany->hasCompleteAddress());
        $this->assertFalse($incompleteCompany->hasCompleteAddress());
    }

    public function test_get_locale_returns_company_locale(): void
    {
        $company = Company::factory()->create(['locale' => 'fr_FR']);

        $this->assertEquals('fr_FR', $company->getLocale());
    }

    public function test_get_locale_falls_back_to_en_us(): void
    {
        $company = Company::factory()->create(['locale' => null]);

        $this->assertEquals('en_US', $company->getLocale());
    }

    public function test_scope_search_finds_companies_by_name(): void
    {
        $company1 = Company::factory()->create(['name' => 'Acme Corporation']);
        $company2 = Company::factory()->create(['name' => 'Beta Industries']);

        $results = Company::search('Acme')->get();

        $this->assertTrue($results->contains($company1));
        $this->assertFalse($results->contains($company2));
    }

    public function test_scope_by_currency_filters_by_currency(): void
    {
        $usdCompany = Company::factory()->create(['currency' => 'USD']);
        $eurCompany = Company::factory()->create(['currency' => 'EUR']);

        $results = Company::byCurrency('USD')->get();

        $this->assertTrue($results->contains($usdCompany));
        $this->assertFalse($results->contains($eurCompany));
    }

    public function test_setting_relationship_exists(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $company->setting());
    }

    public function test_subscription_relationship_exists(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $company->subscription());
    }

    public function test_customization_relationship_exists(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $company->customization());
    }

    public function test_mail_settings_relationship_exists(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $company->mailSettings());
    }

    public function test_contract_configurations_relationship_exists(): void
    {
        $company = Company::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $company->contractConfigurations());
    }

    public function test_round_time_with_no_increment_returns_original(): void
    {
        $company = Company::factory()->create([
            'minimum_billing_increment' => 0,
        ]);

        $rounded = $company->roundTime(1.234);

        $this->assertEquals(1.234, $rounded);
    }

    public function test_round_time_with_increment_rounds_up(): void
    {
        $company = Company::factory()->create([
            'minimum_billing_increment' => 0.25,
            'time_rounding_method' => 'up',
        ]);

        $rounded = $company->roundTime(1.1);

        $this->assertEquals(1.25, $rounded);
    }

    public function test_round_time_with_increment_rounds_down(): void
    {
        $company = Company::factory()->create([
            'minimum_billing_increment' => 0.25,
            'time_rounding_method' => 'down',
        ]);

        $rounded = $company->roundTime(1.2);

        $this->assertEquals(1.0, $rounded);
    }

    public function test_round_time_with_increment_rounds_nearest(): void
    {
        $company = Company::factory()->create([
            'minimum_billing_increment' => 0.25,
            'time_rounding_method' => 'nearest',
        ]);

        $rounded = $company->roundTime(1.1);

        $this->assertEquals(1.0, $rounded);
    }

    public function test_supported_currencies_constant_exists(): void
    {
        $this->assertIsArray(Company::SUPPORTED_CURRENCIES);
        $this->assertArrayHasKey('USD', Company::SUPPORTED_CURRENCIES);
        $this->assertArrayHasKey('EUR', Company::SUPPORTED_CURRENCIES);
        $this->assertArrayHasKey('GBP', Company::SUPPORTED_CURRENCIES);
    }

    public function test_default_currency_constant_exists(): void
    {
        $this->assertEquals('USD', Company::DEFAULT_CURRENCY);
    }
}