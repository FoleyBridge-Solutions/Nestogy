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

class SaasRegistrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create Company 1 (platform company)
        $this->platformCompany = Company::factory()->create(['id' => 1, 'name' => 'Platform Company']);
        
        // Create subscription plans
        $this->subscriptionPlan = SubscriptionPlan::factory()->create([
            'name' => 'Test Plan',
            'slug' => 'test',
            'price_monthly' => 29.00,
            'max_users' => 10,
            'is_active' => true,
        ]);
    }

    public function test_signup_form_displays_correctly()
    {
        $response = $this->get(route('signup.form'));
        
        $response->assertStatus(200);
        $response->assertSee('Create Your Account');
        $response->assertSee('Start your 14-day free trial');
    }

    public function test_subscription_plans_api_returns_active_plans()
    {
        $response = $this->get(route('signup.plans'));
        
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'plans' => [
                '*' => [
                    'id',
                    'name',
                    'price_monthly',
                    'formatted_price',
                    'user_limit_text',
                    'description',
                    'features',
                ]
            ]
        ]);
    }

    public function test_step_validation_works_correctly()
    {
        // Test step 1 validation
        $response = $this->post(route('signup.validate-step'), [
            'step' => 1,
            'company_name' => '',
            'company_email' => 'invalid-email',
        ]);
        
        $response->assertStatus(200);
        $response->assertJson([
            'valid' => false,
            'errors' => [
                'company_name' => ['The company name field is required.'],
                'company_email' => ['The company email field must be a valid email address.'],
            ]
        ]);

        // Test step 1 success
        $response = $this->post(route('signup.validate-step'), [
            'step' => 1,
            'company_name' => 'Test Company',
            'company_email' => 'test@example.com',
        ]);
        
        $response->assertStatus(200);
        $response->assertJson(['valid' => true]);
    }

    public function test_complete_registration_creates_all_records()
    {
        // Mock Stripe service to avoid actual API calls
        $this->mock(\App\Services\StripeSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('createCompleteSubscription')
                ->once()
                ->andReturn([
                    'success' => true,
                    'customer' => (object) ['id' => 'cus_test123'],
                    'subscription' => (object) ['id' => 'sub_test123'],
                    'payment_method' => (object) ['id' => 'pm_test123'],
                ]);
                
            $mock->shouldReceive('storePaymentMethod')
                ->once()
                ->andReturn(true);
        });

        $registrationData = [
            'company_name' => 'Test Company',
            'company_email' => 'test@example.com',
            'company_phone' => '555-1234',
            'admin_name' => 'John Doe',
            'admin_email' => 'john@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_plan_id' => $this->subscriptionPlan->id,
            'payment_method_id' => 'pm_test123',
            'terms_accepted' => 1,
        ];

        $response = $this->post(route('signup.submit'), $registrationData);
        
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));

        // Check that company was created
        $this->assertDatabaseHas('companies', [
            'name' => 'Test Company',
            'email' => 'test@example.com',
        ]);

        // Check that client record was created under Company 1
        $this->assertDatabaseHas('clients', [
            'company_id' => 1,
            'company_name' => 'Test Company',
            'email' => 'john@example.com',
            'subscription_plan_id' => $this->subscriptionPlan->id,
            'subscription_status' => 'trialing',
        ]);

        // Check that admin user was created
        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Check that user has admin role
        $user = User::where('email', 'john@example.com')->first();
        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id,
            'role' => UserSetting::ROLE_ADMIN,
        ]);

        // Check that records are linked
        $company = Company::where('name', 'Test Company')->first();
        $client = Client::where('company_name', 'Test Company')->first();
        
        $this->assertEquals($client->id, $company->client_record_id);
        $this->assertEquals($company->id, $client->company_link_id);
    }

    public function test_registration_fails_with_invalid_data()
    {
        $invalidData = [
            'company_name' => '',
            'admin_email' => 'invalid-email',
            'subscription_plan_id' => 999, // Non-existent plan
        ];

        $response = $this->post(route('signup.submit'), $invalidData);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors([
            'company_name',
            'admin_email',
            'subscription_plan_id',
        ]);
    }

    public function test_registration_fails_when_stripe_fails()
    {
        // Mock Stripe service to simulate failure
        $this->mock(\App\Services\StripeSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('createCompleteSubscription')
                ->once()
                ->andThrow(new \Exception('Stripe error'));
        });

        $registrationData = [
            'company_name' => 'Test Company',
            'company_email' => 'test@example.com',
            'admin_name' => 'John Doe',
            'admin_email' => 'john@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_plan_id' => $this->subscriptionPlan->id,
            'payment_method_id' => 'pm_test123',
            'terms_accepted' => 1,
        ];

        $response = $this->post(route('signup.submit'), $registrationData);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['registration']);

        // Ensure no records were created due to transaction rollback
        $this->assertDatabaseMissing('companies', ['name' => 'Test Company']);
        $this->assertDatabaseMissing('clients', ['company_name' => 'Test Company']);
        $this->assertDatabaseMissing('users', ['email' => 'john@example.com']);
    }

    public function test_user_is_logged_in_after_successful_registration()
    {
        // Mock Stripe service
        $this->mock(\App\Services\StripeSubscriptionService::class, function ($mock) {
            $mock->shouldReceive('createCompleteSubscription')
                ->once()
                ->andReturn([
                    'success' => true,
                    'customer' => (object) ['id' => 'cus_test123'],
                    'subscription' => (object) ['id' => 'sub_test123'],
                    'payment_method' => (object) ['id' => 'pm_test123'],
                ]);
                
            $mock->shouldReceive('storePaymentMethod')
                ->once()
                ->andReturn(true);
        });

        $registrationData = [
            'company_name' => 'Test Company',
            'company_email' => 'test@example.com',
            'admin_name' => 'John Doe',
            'admin_email' => 'john@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_plan_id' => $this->subscriptionPlan->id,
            'payment_method_id' => 'pm_test123',
            'terms_accepted' => 1,
        ];

        $response = $this->post(route('signup.submit'), $registrationData);
        
        $this->assertAuthenticated();
        
        $user = auth()->user();
        $this->assertEquals('john@example.com', $user->email);
        $this->assertEquals('John Doe', $user->name);
    }

    public function test_duplicate_email_registration_fails()
    {
        // Create an existing user
        User::factory()->create(['email' => 'john@example.com']);

        $registrationData = [
            'company_name' => 'Test Company',
            'company_email' => 'test@example.com',
            'admin_name' => 'John Doe',
            'admin_email' => 'john@example.com', // Duplicate email
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'subscription_plan_id' => $this->subscriptionPlan->id,
            'terms_accepted' => 1,
        ];

        $response = $this->post(route('signup.submit'), $registrationData);
        
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['admin_email']);
    }
}