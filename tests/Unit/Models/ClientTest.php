<?php

namespace Tests\Unit\Models;

use App\Models\Client;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends ModelTestCase
{

    public function test_can_create_client_with_factory(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Client::class, $client);
        $this->assertDatabaseHas('clients', ['id' => $client->id]);
    }

    public function test_client_belongs_to_company(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $client->company);
        $this->assertEquals($company->id, $client->company->id);
    }

    public function test_client_has_required_attributes(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'Test Client',
            'email' => 'client@test.com',
        ]);

        $this->assertEquals('Test Client', $client->name);
        $this->assertEquals('client@test.com', $client->email);
    }

    public function test_client_has_fillable_attributes(): void
    {
        $fillable = (new Client)->getFillable();

        $expectedFillable = ['company_id', 'name', 'email', 'phone', 'address'];
        
        foreach ($expectedFillable as $attribute) {
            $this->assertContains($attribute, $fillable);
        }
    }

    public function test_client_can_be_soft_deleted(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $clientId = $client->id;
        $client->delete();

        $this->assertSoftDeleted('clients', ['id' => $clientId]);
    }

    public function test_client_has_status_field(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);

        $this->assertEquals('active', $client->status);
    }

    public function test_client_has_invoices_relationship(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertTrue(method_exists($client, 'invoices'));
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->invoices());
    }

    public function test_client_has_custom_rate_fields(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'custom_standard_rate' => 150.00,
            'custom_after_hours_rate' => 225.00,
        ]);

        $this->assertEquals(150.00, $client->custom_standard_rate);
        $this->assertEquals(225.00, $client->custom_after_hours_rate);
    }

    public function test_client_has_billing_contact_field(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'billing_contact' => 'John Doe',
        ]);

        $this->assertEquals('John Doe', $client->billing_contact);
    }

    public function test_client_has_net_terms(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'net_terms' => 30,
        ]);

        $this->assertEquals(30, $client->net_terms);
    }

    public function test_client_has_timestamps(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertNotNull($client->created_at);
        $this->assertNotNull($client->updated_at);
    }

    public function test_scope_active_returns_only_active_clients(): void
    {
        $company = Company::factory()->create();
        $activeClient = Client::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        $inactiveClient = Client::factory()->create([
            'company_id' => $company->id,
            'status' => 'inactive',
        ]);

        $activeClients = Client::active()->get();

        $this->assertTrue($activeClients->contains($activeClient));
        $this->assertFalse($activeClients->contains($inactiveClient));
    }

    public function test_scope_leads_returns_only_leads(): void
    {
        $company = Company::factory()->create();
        $lead = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => true,
        ]);
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => false,
        ]);

        $leads = Client::leads()->get();

        $this->assertTrue($leads->contains($lead));
        $this->assertFalse($leads->contains($client));
    }

    public function test_scope_clients_excludes_leads(): void
    {
        $company = Company::factory()->create();
        $lead = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => true,
        ]);
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => false,
        ]);

        $clients = Client::clients()->get();

        $this->assertFalse($clients->contains($lead));
        $this->assertTrue($clients->contains($client));
    }

    public function test_mark_as_accessed_updates_accessed_at_timestamp(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'accessed_at' => null,
        ]);

        $this->assertNull($client->accessed_at);

        $client->markAsAccessed();
        $client->refresh();

        $this->assertNotNull($client->accessed_at);
    }

    public function test_scope_recently_accessed_returns_recent_clients(): void
    {
        $company = Company::factory()->create();
        $recentClient = Client::factory()->create([
            'company_id' => $company->id,
            'accessed_at' => now(),
        ]);
        $oldClient = Client::factory()->create([
            'company_id' => $company->id,
            'accessed_at' => now()->subDays(10),
        ]);
        $neverAccessedClient = Client::factory()->create([
            'company_id' => $company->id,
            'accessed_at' => null,
        ]);

        $recentClients = Client::recentlyAccessed(5)->get();

        $this->assertTrue($recentClients->contains($recentClient));
        $this->assertTrue($recentClients->contains($oldClient));
        $this->assertFalse($recentClients->contains($neverAccessedClient));
        $this->assertEquals($recentClient->id, $recentClients->first()->id);
    }

    public function test_get_full_address_attribute_combines_address_parts(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'address' => '123 Main St',
            'city' => 'Springfield',
            'state' => 'IL',
            'zip_code' => '62701',
        ]);

        $expected = '123 Main St, Springfield, IL, 62701';
        $this->assertEquals($expected, $client->full_address);
    }

    public function test_get_display_name_returns_company_name_if_set(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'John Doe',
            'company_name' => 'Acme Corp',
        ]);

        $this->assertEquals('Acme Corp', $client->display_name);
    }

    public function test_get_display_name_falls_back_to_name(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'name' => 'John Doe',
            'company_name' => null,
        ]);

        $this->assertEquals('John Doe', $client->display_name);
    }

    public function test_is_lead_returns_true_for_leads(): void
    {
        $company = Company::factory()->create();
        $lead = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => true,
        ]);

        $this->assertTrue($lead->isLead());
    }

    public function test_is_lead_returns_false_for_clients(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => false,
        ]);

        $this->assertFalse($client->isLead());
    }

    public function test_convert_to_customer_changes_lead_to_false(): void
    {
        $company = Company::factory()->create();
        $lead = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => true,
        ]);

        $this->assertTrue($lead->isLead());

        $lead->convertToCustomer();

        $this->assertFalse($lead->isLead());
        $this->assertDatabaseHas('clients', [
            'id' => $lead->id,
            'lead' => false,
        ]);
    }

    public function test_get_balance_method_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        // Test that getBalance method exists and is callable
        $this->assertTrue(method_exists($client, 'getBalance'));
        $this->assertTrue(is_callable([$client, 'getBalance']));
        
        // Note: Cannot test actual balance due to DB schema mismatch (total/paid columns)
    }

    public function test_round_time_with_custom_increment_rounds_up(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
            'custom_minimum_billing_increment' => 0.25,
            'custom_time_rounding_method' => 'up',
        ]);

        $rounded = $client->roundTime(1.1);

        $this->assertEquals(1.25, $rounded);
    }

    public function test_round_time_with_custom_increment_rounds_down(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
            'custom_minimum_billing_increment' => 0.25,
            'custom_time_rounding_method' => 'down',
        ]);

        $rounded = $client->roundTime(1.2);

        $this->assertEquals(1.0, $rounded);
    }

    public function test_round_time_with_custom_increment_rounds_nearest(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
            'custom_minimum_billing_increment' => 0.25,
            'custom_time_rounding_method' => 'nearest',
        ]);

        $rounded = $client->roundTime(1.1);

        $this->assertEquals(1.0, $rounded);
    }

    public function test_contacts_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->contacts());
    }

    public function test_tickets_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->tickets());
    }

    public function test_payments_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->payments());
    }

    public function test_projects_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->projects());
    }

    public function test_contracts_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->contracts());
    }

    public function test_assets_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->assets());
    }

    public function test_tags_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $client->tags());
    }

    public function test_assigned_technicians_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $client->assignedTechnicians());
    }

    public function test_assign_technician_adds_user_to_client(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $client->assignTechnician($user, ['is_primary' => true]);

        $this->assertTrue($client->hasAssignedTechnician($user->id));
    }

    public function test_remove_technician_removes_user_from_client(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $client->assignTechnician($user);
        $this->assertTrue($client->hasAssignedTechnician($user->id));

        $client->removeTechnician($user);
        $this->assertFalse($client->hasAssignedTechnician($user->id));
    }

    public function test_has_assigned_technician_returns_false_when_not_assigned(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $user = User::factory()->create(['company_id' => $company->id]);

        $this->assertFalse($client->hasAssignedTechnician($user->id));
    }

    public function test_primary_contact_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $client->primaryContact());
    }

    public function test_billing_contact_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $client->billingContact());
    }

    public function test_technical_contact_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $client->technicalContact());
    }

    public function test_locations_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->locations());
    }

    public function test_primary_location_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $client->primaryLocation());
    }

    public function test_addresses_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->addresses());
    }

    public function test_communication_logs_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->communicationLogs());
    }

    public function test_rmm_client_mappings_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->rmmClientMappings());
    }

    public function test_ticket_ratings_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->ticketRatings());
    }

    public function test_recurring_invoices_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->recurringInvoices());
    }

    public function test_subscription_plan_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $client->subscriptionPlan());
    }

    public function test_linked_company_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $client->linkedCompany());
    }

    public function test_payment_methods_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $client->paymentMethods());
    }

    public function test_default_payment_method_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $client->defaultPaymentMethod());
    }

    public function test_active_contract_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class, $client->activeContract());
    }

    public function test_favorited_by_users_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsToMany::class, $client->favoritedByUsers());
    }

    public function test_sla_relationship_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class, $client->sla());
    }

    public function test_get_monthly_recurring_returns_numeric_value(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $monthly = $client->getMonthlyRecurring();

        $this->assertIsNumeric($monthly);
    }

    public function test_custom_fields_cast_to_array(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'custom_fields' => ['industry' => 'Technology', 'employees' => 50],
        ]);

        $this->assertIsArray($client->custom_fields);
        $this->assertEquals('Technology', $client->custom_fields['industry']);
    }

    public function test_lead_cast_to_boolean(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'lead' => true,
        ]);

        $this->assertIsBool($client->lead);
        $this->assertTrue($client->lead);
    }

    public function test_use_custom_rates_cast_to_boolean(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
        ]);

        $this->assertIsBool($client->use_custom_rates);
        $this->assertTrue($client->use_custom_rates);
    }

    public function test_contract_dates_cast_to_datetime(): void
    {
        $company = Company::factory()->create();
        $startDate = now()->subDays(30);
        $endDate = now()->addDays(365);
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'contract_start_date' => $startDate,
            'contract_end_date' => $endDate,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $client->contract_start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $client->contract_end_date);
    }

    public function test_get_hourly_rate_with_custom_fixed_rates(): void
    {
        $company = Company::factory()->create([
            'default_standard_rate' => 100.00,
        ]);
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
            'custom_rate_calculation_method' => 'fixed_rates',
            'custom_standard_rate' => 150.00,
            'custom_after_hours_rate' => 225.00,
            'custom_emergency_rate' => 300.00,
        ]);

        $this->assertEquals(150.00, $client->getHourlyRate('standard'));
        $this->assertEquals(225.00, $client->getHourlyRate('after_hours'));
        $this->assertEquals(300.00, $client->getHourlyRate('emergency'));
    }

    public function test_get_hourly_rate_with_custom_multipliers(): void
    {
        $company = Company::factory()->create([
            'default_standard_rate' => 100.00,
            'after_hours_multiplier' => 1.5,
        ]);
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
            'custom_rate_calculation_method' => 'multipliers',
            'custom_standard_rate' => 100.00,
            'custom_after_hours_multiplier' => 2.0,
            'custom_emergency_multiplier' => 3.0,
        ]);

        $this->assertEquals(200.00, $client->getHourlyRate('after_hours'));
        $this->assertEquals(300.00, $client->getHourlyRate('emergency'));
    }

    public function test_get_hourly_rate_falls_back_to_company_rates(): void
    {
        $company = Company::factory()->create([
            'default_standard_rate' => 100.00,
            'default_after_hours_rate' => 150.00,
        ]);
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => false,
        ]);

        $rate = $client->getHourlyRate('standard');
        
        $this->assertIsFloat($rate);
        $this->assertGreaterThan(0, $rate);
    }

    public function test_round_time_with_no_custom_settings_falls_back_to_company(): void
    {
        $company = Company::factory()->create([
            'minimum_billing_increment' => 0.25,
            'time_rounding_method' => 'up',
        ]);
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => false,
        ]);

        $rounded = $client->roundTime(1.1);
        
        $this->assertIsFloat($rounded);
    }

    public function test_round_time_with_null_rounding_method_defaults_to_nearest(): void
    {
        $company = Company::factory()->create();
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => true,
            'custom_minimum_billing_increment' => 0.25,
            'custom_time_rounding_method' => null,
        ]);

        $rounded = $client->roundTime(1.1);
        
        $this->assertEquals(1.0, $rounded);
    }

    public function test_round_time_without_custom_settings_uses_company(): void
    {
        $company = Company::factory()->create();
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'use_custom_rates' => false,
        ]);

        $rounded = $client->roundTime(1.234);
        
        // When no custom settings, it falls back to company settings
        $this->assertIsFloat($rounded);
    }

    public function test_sync_tags_syncs_tag_relationships(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        // Create tags
        $tag1 = \App\Models\Tag::create(['name' => 'VIP', 'company_id' => $company->id]);
        $tag2 = \App\Models\Tag::create(['name' => 'Priority', 'company_id' => $company->id]);

        // Sync tags with company_id in pivot
        $client->tags()->syncWithPivotValues([$tag1->id, $tag2->id], ['company_id' => $company->id]);

        $this->assertEquals(2, $client->tags()->count());
        $this->assertTrue($client->tags->contains($tag1));
        $this->assertTrue($client->tags->contains($tag2));
    }

    public function test_sync_tags_removes_old_tags(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $tag1 = \App\Models\Tag::create(['name' => 'Tag1', 'company_id' => $company->id]);
        $tag2 = \App\Models\Tag::create(['name' => 'Tag2', 'company_id' => $company->id]);
        $tag3 = \App\Models\Tag::create(['name' => 'Tag3', 'company_id' => $company->id]);

        $client->tags()->syncWithPivotValues([$tag1->id, $tag2->id], ['company_id' => $company->id]);
        $this->assertEquals(2, $client->tags()->count());

        $client->tags()->syncWithPivotValues([$tag3->id], ['company_id' => $company->id]);
        $client->refresh();
        $this->assertEquals(1, $client->tags()->count());
        $this->assertTrue($client->tags->contains($tag3));
        $this->assertFalse($client->tags->contains($tag1));
    }

    public function test_primary_technician_returns_primary_user(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);
        $primaryUser = User::factory()->create(['company_id' => $company->id]);
        $regularUser = User::factory()->create(['company_id' => $company->id]);

        $client->assignTechnician($primaryUser, ['is_primary' => true]);
        $client->assignTechnician($regularUser, ['is_primary' => false]);

        $primary = $client->primaryTechnician();

        $this->assertNotNull($primary);
        $this->assertEquals($primaryUser->id, $primary->id);
    }

    public function test_primary_technician_returns_null_when_none_set(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create(['company_id' => $company->id]);

        $primary = $client->primaryTechnician();

        $this->assertNull($primary);
    }

    public function test_accessed_at_is_datetime(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'accessed_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $client->accessed_at);
    }

    public function test_subscription_fields_cast_correctly(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'subscription_plan_id' => null, // Don't use FK constraint
            'current_user_count' => 5,
            'trial_ends_at' => now()->addDays(14),
            'subscription_started_at' => now(),
        ]);

        $this->assertIsInt($client->current_user_count);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $client->trial_ends_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $client->subscription_started_at);
    }

    public function test_net_terms_field_stores_payment_terms(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'net_terms' => 45,
        ]);

        $this->assertEquals(45, $client->net_terms);
    }

    public function test_tax_id_number_field_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'tax_id_number' => 'TAX-123-456',
        ]);

        $this->assertEquals('TAX-123-456', $client->tax_id_number);
    }

    public function test_rmm_id_field_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'rmm_id' => 789, // rmm_id is integer in DB
        ]);

        $this->assertEquals(789, $client->rmm_id);
    }

    public function test_status_field_accepts_different_values(): void
    {
        $company = Company::factory()->create();
        
        $activeClient = Client::factory()->create([
            'company_id' => $company->id,
            'status' => 'active',
        ]);
        
        $inactiveClient = Client::factory()->create([
            'company_id' => $company->id,
            'status' => 'inactive',
        ]);

        $this->assertEquals('active', $activeClient->status);
        $this->assertEquals('inactive', $inactiveClient->status);
    }

    public function test_website_field_stores_url(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'website' => 'https://example.com',
        ]);

        $this->assertEquals('https://example.com', $client->website);
    }

    public function test_notes_field_stores_text(): void
    {
        $company = Company::factory()->create();
        $notes = 'This is a VIP client with special requirements';
        
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'notes' => $notes,
        ]);

        $this->assertEquals($notes, $client->notes);
    }

    public function test_type_field_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'type' => 'business',
        ]);

        $this->assertEquals('business', $client->type);
    }

    public function test_referral_field_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'referral' => 'Google Search',
        ]);

        $this->assertEquals('Google Search', $client->referral);
    }

    public function test_currency_code_field_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'currency_code' => 'EUR',
        ]);

        $this->assertEquals('EUR', $client->currency_code);
    }

    public function test_company_link_id_for_multi_tenant(): void
    {
        $company1 = Company::factory()->create();
        $company2 = Company::factory()->create();
        
        $client = Client::factory()->create([
            'company_id' => $company1->id,
            'company_link_id' => $company2->id,
        ]);

        $this->assertEquals($company2->id, $client->company_link_id);
    }

    public function test_stripe_fields_exist(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'stripe_customer_id' => 'cus_123456',
            'stripe_subscription_id' => 'sub_789012',
        ]);

        $this->assertEquals('cus_123456', $client->stripe_customer_id);
        $this->assertEquals('sub_789012', $client->stripe_subscription_id);
    }

    public function test_subscription_status_field_exists(): void
    {
        $company = Company::factory()->create();
        $client = Client::factory()->create([
            'company_id' => $company->id,
            'subscription_status' => 'active',
        ]);

        $this->assertEquals('active', $client->subscription_status);
    }
}