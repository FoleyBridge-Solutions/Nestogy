<?php

namespace Tests\Unit\Models;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Contact;
use App\Domains\Company\Models\Company;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class ContactPasswordTest extends TestCase
{
    use RefreshesDatabase;

    protected Company $company;
    protected Client $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);
    }

    /** @test */
    public function it_can_set_password_using_set_password_method(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
        ]);

        // Initially password should be null
        $this->assertNull($contact->password_hash);

        // Set password using the setPassword method
        $contact->setPassword('SecurePassword123!');

        // Refresh from database
        $contact = $contact->fresh();

        // Verify password was set
        $this->assertNotNull($contact->password_hash);
        $this->assertTrue(Hash::check('SecurePassword123!', $contact->password_hash));
        $this->assertEquals('password', $contact->auth_method);
        $this->assertNotNull($contact->password_changed_at);
    }

    /** @test */
    public function it_can_verify_password_after_setting(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
        ]);

        $password = 'MyTestPassword123!';
        $contact->setPassword($password);

        $contact = $contact->fresh();

        $this->assertTrue($contact->verifyPassword($password));
        $this->assertFalse($contact->verifyPassword('WrongPassword'));
    }

    /** @test */
    public function it_cannot_mass_assign_password_hash(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
        ]);

        // Attempt mass assignment (should fail silently)
        $contact->update([
            'password_hash' => Hash::make('TestPassword123!'),
            'password_changed_at' => now(),
        ]);

        $contact = $contact->fresh();

        // Password should still be null because mass assignment is blocked
        $this->assertNull($contact->password_hash);
    }

    /** @test */
    public function it_can_set_password_via_direct_assignment(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
        ]);

        // Direct assignment (should work)
        $hashedPassword = Hash::make('DirectPassword123!');
        $contact->password_hash = $hashedPassword;
        $contact->password_changed_at = now();
        $contact->auth_method = 'password';
        $contact->save();

        $contact = $contact->fresh();

        // Verify password was set
        $this->assertNotNull($contact->password_hash);
        $this->assertEquals($hashedPassword, $contact->password_hash);
        $this->assertNotNull($contact->password_changed_at);
    }

    /** @test */
    public function it_updates_auth_method_when_setting_password(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
            'auth_method' => 'none',
        ]);

        $contact->setPassword('NewPassword123!');
        $contact = $contact->fresh();

        $this->assertEquals('password', $contact->auth_method);
    }

    /** @test */
    public function it_can_set_pin_using_set_pin_method(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'has_portal_access' => true,
        ]);

        $this->assertNull($contact->pin);

        $contact->setPin('1234');
        $contact = $contact->fresh();

        $this->assertNotNull($contact->pin);
        $this->assertTrue(Hash::check('1234', $contact->pin));
        $this->assertEquals('pin', $contact->auth_method);
    }

    /** @test */
    public function it_can_update_existing_password(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
        ]);

        // Set initial password
        $contact->setPassword('InitialPassword123!');
        $contact = $contact->fresh();
        $initialHash = $contact->password_hash;

        // Update password
        $contact->setPassword('UpdatedPassword456!');
        $contact = $contact->fresh();

        // Hash should be different
        $this->assertNotEquals($initialHash, $contact->password_hash);
        $this->assertTrue($contact->verifyPassword('UpdatedPassword456!'));
        $this->assertFalse($contact->verifyPassword('InitialPassword123!'));
    }

    /** @test */
    public function password_changed_at_is_updated_when_setting_password(): void
    {
        $contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'has_portal_access' => true,
        ]);

        $this->assertNull($contact->password_changed_at);

        $contact->setPassword('TestPassword123!');
        $contact = $contact->fresh();

        $this->assertNotNull($contact->password_changed_at);
        $this->assertTrue($contact->password_changed_at->isToday());
    }
}
