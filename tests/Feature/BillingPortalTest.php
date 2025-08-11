<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BillingPortalTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $user;
    protected $company;
    protected $client;
    protected $subscriptionPlan;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create platform company
        Company::factory()->create(['id' => 1, 'name' => 'Platform Company']);
        
        // Create tenant company
        $this->company = Company::factory()->create([
            'name' => 'Test Tenant Company',
        ]);
        
        // Create subscription plan
        $this->subscriptionPlan = SubscriptionPlan::factory()->create([
            'name' => 'Professional',
            'price_monthly' => 79.00,
            'max_users' => 25,
            'is_active' => true,
        ]);
        
        // Create client record (billing record in Company 1)
        $this->client = Client::factory()->create([
            'company_id' => 1, // Company 1
            'company_name' => 'Test Tenant Company',
            'email' => 'admin@tenant.com',
            'company_link_id' => $this->company->id,
            'subscription_plan_id' => $this->subscriptionPlan->id,
            'subscription_status' => 'active',
            'stripe_customer_id' => 'cus_test123',
            'stripe_subscription_id' => 'sub_test123',
        ]);
        
        // Link company to client
        $this->company->update(['client_record_id' => $this->client->id]);
        
        // Create user in tenant company
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
            'email' => 'admin@tenant.com',
        ]);
        
        // Create user settings with admin role
        UserSetting::factory()->create([
            'user_id' => $this->user->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);
    }

    public function test_billing_index_shows_subscription_details()
    {
        $response = $this->actingAs($this->user)->get(route('billing.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Billing & Subscription');
        $response->assertSee('Professional'); // Plan name
        $response->assertSee('$79.00/month'); // Plan price
        $response->assertSee('Active'); // Status
    }

    public function test_billing_index_redirects_for_company_without_billing()
    {
        // Create a company without billing setup
        $companyWithoutBilling = Company::factory()->create();
        $userWithoutBilling = User::factory()->create(['company_id' => $companyWithoutBilling->id]);
        
        $response = $this->actingAs($userWithoutBilling)->get(route('billing.index'));
        
        $response->assertStatus(200);
        $response->assertSee('not-setup'); // Should show not setup view
    }

    public function test_trial_warning_displays_for_trialing_subscriptions()
    {
        // Update client to be trialing with trial ending soon
        $this->client->update([
            'subscription_status' => 'trialing',
            'trial_ends_at' => now()->addDays(2),
        ]);
        
        $response = $this->actingAs($this->user)->get(route('billing.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Your trial ends in 2 days');
    }

    public function test_past_due_warning_displays_for_past_due_subscriptions()
    {
        $this->client->update(['subscription_status' => 'past_due']);
        
        $response = $this->actingAs($this->user)->get(route('billing.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Payment Required');
        $response->assertSee('past due');
    }

    public function test_subscription_details_page_loads()
    {
        $response = $this->actingAs($this->user)->get(route('billing.subscription'));
        
        $response->assertStatus(200);
        $response->assertSee('Professional');
        $response->assertSee('sub_test123'); // Stripe subscription ID
    }

    public function test_change_plan_page_shows_available_plans()
    {
        // Create another plan
        $anotherPlan = SubscriptionPlan::factory()->create([
            'name' => 'Enterprise',
            'price_monthly' => 199.00,
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)->get(route('billing.change-plan'));
        
        $response->assertStatus(200);
        $response->assertSee('Enterprise'); // Should see other plan
        $response->assertDontSee('Professional'); // Should not see current plan
    }

    public function test_plan_change_updates_subscription()
    {
        // Mock Stripe service
        $this->mock(\App\Services\StripeSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('updateSubscription')
                ->once()
                ->with('sub_test123', 'price_new123')
                ->andReturn(true);
        });
        
        // Create new plan
        $newPlan = SubscriptionPlan::factory()->create([
            'name' => 'Enterprise',
            'price_monthly' => 199.00,
            'stripe_price_id' => 'price_new123',
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($this->user)->patch(route('billing.update-plan'), [
            'subscription_plan_id' => $newPlan->id,
        ]);
        
        $response->assertStatus(302);
        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('success');
        
        // Check that client was updated
        $this->client->refresh();
        $this->assertEquals($newPlan->id, $this->client->subscription_plan_id);
    }

    public function test_plan_change_fails_with_invalid_plan()
    {
        $response = $this->actingAs($this->user)->patch(route('billing.update-plan'), [
            'subscription_plan_id' => 999, // Non-existent plan
        ]);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['subscription_plan_id']);
    }

    public function test_subscription_cancellation_works()
    {
        // Mock Stripe service
        $this->mock(\App\Services\StripeSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('cancelSubscription')
                ->once()
                ->with('sub_test123', false) // Cancel at period end
                ->andReturn(true);
        });
        
        $response = $this->actingAs($this->user)->post(route('billing.cancel-subscription'), [
            'reason' => 'Not needed anymore',
            'feedback' => 'Service was good but too expensive',
        ]);
        
        $response->assertStatus(302);
        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('success');
    }

    public function test_subscription_reactivation_works()
    {
        // Mock Stripe service
        $this->mock(\App\Services\StripeSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('updateSubscription')
                ->once()
                ->with('sub_test123', null) // Remove cancellation
                ->andReturn(true);
        });
        
        $response = $this->actingAs($this->user)->post(route('billing.reactivate-subscription'));
        
        $response->assertStatus(302);
        $response->assertRedirect(route('billing.index'));
        $response->assertSessionHas('success');
    }

    public function test_usage_page_shows_current_usage()
    {
        $response = $this->actingAs($this->user)->get(route('billing.usage'));
        
        $response->assertStatus(200);
        $response->assertSee('Usage'); // Should show usage metrics
    }

    public function test_invoices_page_loads()
    {
        $response = $this->actingAs($this->user)->get(route('billing.invoices'));
        
        $response->assertStatus(200);
        $response->assertSee('Invoice'); // Should show invoices page
    }

    public function test_payment_methods_page_loads()
    {
        $response = $this->actingAs($this->user)->get(route('billing.payment-methods'));
        
        $response->assertStatus(200);
        $response->assertSee('Payment Methods');
    }

    public function test_billing_portal_redirect_works()
    {
        // Mock Stripe client
        $this->mock(\Stripe\StripeClient::class, function ($mock) {
            $mockSession = (object) ['url' => 'https://billing.stripe.com/session123'];
            
            $mock->billingPortal = (object) [
                'sessions' => (object) [
                    'create' => function($params) use ($mockSession) {
                        return $mockSession;
                    }
                ]
            ];
        });
        
        $response = $this->actingAs($this->user)->get(route('billing.portal'));
        
        $response->assertStatus(302);
        $response->assertRedirect('https://billing.stripe.com/session123');
    }

    public function test_unauthorized_user_cannot_access_billing()
    {
        // Create user from different company
        $otherCompany = Company::factory()->create();
        $otherUser = User::factory()->create(['company_id' => $otherCompany->id]);
        
        $response = $this->actingAs($otherUser)->get(route('billing.index'));
        
        // Should either redirect or show no billing setup
        $response->assertStatus(200);
        $response->assertSee('not-setup');
    }

    public function test_super_admin_can_access_subscription_management()
    {
        // Create super admin user in Company 1
        $superAdminUser = User::factory()->create(['company_id' => 1]);
        UserSetting::factory()->create([
            'user_id' => $superAdminUser->id,
            'role' => UserSetting::ROLE_SUPER_ADMIN,
        ]);
        
        $response = $this->actingAs($superAdminUser)->get(route('admin.subscriptions.index'));
        
        $response->assertStatus(200);
        $response->assertSee('Subscription Management');
    }

    public function test_regular_user_cannot_access_subscription_management()
    {
        $response = $this->actingAs($this->user)->get(route('admin.subscriptions.index'));
        
        $response->assertStatus(403); // Forbidden
    }
}