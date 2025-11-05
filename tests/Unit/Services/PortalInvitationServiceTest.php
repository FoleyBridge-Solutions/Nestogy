<?php

namespace Tests\Unit\Services;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Contact;
use App\Domains\Client\Services\PortalInvitationService;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PortalInvitationServiceTest extends TestCase
{
    use RefreshesDatabase;

    protected PortalInvitationService $service;
    protected Company $company;
    protected Client $client;
    protected Contact $contact;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new PortalInvitationService();
        $this->company = Company::factory()->create();
        $this->client = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'active',
        ]);
        $this->contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'name' => 'Test Contact',
        ]);
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
    }

    public function test_send_invitation_creates_token_that_can_be_validated()
    {
        // Mock email sending to avoid actual email sending
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $result = $this->service->sendInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertNotNull($this->contact->fresh()->invitation_token);
        $this->assertNotNull($this->contact->fresh()->invitation_expires_at);
    }

    public function test_invitation_token_is_stored_despite_being_guarded()
    {
        // This is the critical test that would have caught the bug
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $result = $this->service->sendInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        
        // Refresh contact from database
        $this->contact->refresh();
        
        // The invitation_token field should NOT be null
        $this->assertNotNull(
            $this->contact->invitation_token,
            'invitation_token should be stored in database even though it is in the guarded array'
        );
    }

    public function test_validate_token_finds_contact_with_valid_token()
    {
        // Create a plain token
        $plainToken = 'test-invitation-token-12345678901234567890';
        
        // Manually set up the invitation using direct assignment (bypasses mass assignment protection)
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        // Validate the token
        $foundContact = $this->service->validateToken($plainToken);

        $this->assertNotNull($foundContact);
        $this->assertEquals($this->contact->id, $foundContact->id);
    }

    public function test_validate_token_returns_null_for_expired_invitation()
    {
        $plainToken = 'test-expired-token-12345678901234567890';
        
        // Set up an expired invitation
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->subDays(1); // Expired
        $this->contact->save();

        $foundContact = $this->service->validateToken($plainToken);

        $this->assertNull($foundContact);
    }

    public function test_validate_token_returns_null_for_invalid_token()
    {
        $plainToken = 'valid-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        // Try to validate with wrong token
        $foundContact = $this->service->validateToken('wrong-token-abcdefghijklmnopqrstuvwxyz');

        $this->assertNull($foundContact);
    }

    public function test_invitation_status_is_set_to_sent()
    {
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $result = $this->service->sendInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertEquals('sent', $this->contact->fresh()->invitation_status);
    }

    public function test_invitation_expires_at_is_set_correctly()
    {
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $beforeSending = now()->subSecond(); // Add 1 second buffer
        $result = $this->service->sendInvitation($this->contact, $this->user);
        $afterSending = now()->addSecond(); // Add 1 second buffer

        $this->assertTrue($result['success']);
        
        $contact = $this->contact->fresh();
        $expectedMinExpiry = $beforeSending->copy()->addHours(72);
        $expectedMaxExpiry = $afterSending->copy()->addHours(72);
        
        $this->assertTrue(
            $contact->invitation_expires_at->between($expectedMinExpiry, $expectedMaxExpiry),
            'Invitation should expire in 72 hours'
        );
    }

    public function test_has_portal_access_is_enabled_when_invitation_sent()
    {
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $this->assertFalse($this->contact->has_portal_access);

        $result = $this->service->sendInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertTrue($this->contact->fresh()->has_portal_access);
    }

    public function test_accept_invitation_sets_password()
    {
        $plainToken = 'test-accept-token-12345678901234567890';
        $password = 'SecurePass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $result = $this->service->acceptInvitation($plainToken, $password);

        $this->assertTrue($result['success']);
        
        $contact = $this->contact->fresh();
        $this->assertNotNull($contact->password_hash);
        $this->assertTrue(Hash::check($password, $contact->password_hash));
    }

    public function test_accept_invitation_marks_invitation_as_accepted()
    {
        $plainToken = 'test-accept-token-12345678901234567890';
        $password = 'SecurePass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $result = $this->service->acceptInvitation($plainToken, $password);

        $this->assertTrue($result['success']);
        $this->assertEquals('accepted', $this->contact->fresh()->invitation_status);
    }

    public function test_accept_invitation_clears_invitation_token()
    {
        $plainToken = 'test-accept-token-12345678901234567890';
        $password = 'SecurePass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $result = $this->service->acceptInvitation($plainToken, $password);

        $this->assertTrue($result['success']);
        $this->assertNull($this->contact->fresh()->invitation_token);
    }

    public function test_accept_invitation_sets_email_verified_at()
    {
        $plainToken = 'test-accept-token-12345678901234567890';
        $password = 'SecurePass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $this->assertNull($this->contact->email_verified_at);

        $result = $this->service->acceptInvitation($plainToken, $password);

        $this->assertTrue($result['success']);
        $this->assertNotNull($this->contact->fresh()->email_verified_at);
    }

    public function test_accept_invitation_fails_with_expired_token()
    {
        $plainToken = 'test-expired-token-12345678901234567890';
        $password = 'SecurePass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->subDay(); // Expired yesterday
        $this->contact->save();

        $result = $this->service->acceptInvitation($plainToken, $password);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('expired', strtolower($result['message']));
    }

    public function test_accept_invitation_validates_password_requirements()
    {
        $plainToken = 'test-accept-token-12345678901234567890';
        $weakPassword = 'weak'; // Too short, missing uppercase, missing numbers
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $result = $this->service->acceptInvitation($plainToken, $weakPassword);

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('errors', $result);
        $this->assertNotEmpty($result['errors']);
    }

    public function test_resend_invitation_generates_new_token()
    {
        // Set up existing invitation
        $oldToken = 'old-token-12345678901234567890';
        $this->contact->invitation_token = Hash::make($oldToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(1);
        $this->contact->save();

        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        // Resend invitation
        $result = $this->service->resendInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        
        // The token should have changed
        $contact = $this->contact->fresh();
        $this->assertFalse(Hash::check($oldToken, $contact->invitation_token));
    }

    public function test_revoke_invitation_clears_token()
    {
        $this->contact->invitation_token = Hash::make('some-token');
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $result = $this->service->revokeInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertNull($this->contact->fresh()->invitation_token);
    }

    public function test_revoke_invitation_sets_status_to_revoked()
    {
        $this->contact->invitation_token = Hash::make('some-token');
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $result = $this->service->revokeInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertEquals('revoked', $this->contact->fresh()->invitation_status);
    }

    public function test_revoke_invitation_disables_portal_access()
    {
        $this->contact->invitation_token = Hash::make('some-token');
        $this->contact->invitation_status = 'sent';
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $result = $this->service->revokeInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertFalse($this->contact->fresh()->has_portal_access);
    }

    public function test_send_invitation_fails_without_email()
    {
        $contactWithoutEmail = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => null,
        ]);

        $result = $this->service->sendInvitation($contactWithoutEmail, $this->user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('email', strtolower($result['message']));
    }

    public function test_send_invitation_fails_for_inactive_client()
    {
        $inactiveClient = Client::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'inactive',
        ]);
        
        $contactWithInactiveClient = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $inactiveClient->id,
            'email' => 'test@inactive.com',
        ]);

        $result = $this->service->sendInvitation($contactWithInactiveClient, $this->user);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('active', strtolower($result['message']));
    }

    public function test_update_expired_invitations_changes_status()
    {
        // Create contacts with expired invitations
        $expiredContact1 = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invitation_status' => 'sent',
            'invitation_expires_at' => now()->subDay(),
        ]);
        
        $expiredContact2 = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invitation_status' => 'sent',
            'invitation_expires_at' => now()->subHours(2),
        ]);
        
        // Create a non-expired contact
        $validContact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'invitation_status' => 'sent',
            'invitation_expires_at' => now()->addDay(),
        ]);

        $count = $this->service->updateExpiredInvitations();

        $this->assertEquals(2, $count);
        $this->assertEquals('expired', $expiredContact1->fresh()->invitation_status);
        $this->assertEquals('expired', $expiredContact2->fresh()->invitation_status);
        $this->assertEquals('sent', $validContact->fresh()->invitation_status);
    }

    public function test_contact_has_valid_invitation_method_works_correctly()
    {
        // Valid invitation
        $this->contact->invitation_token = Hash::make('valid-token');
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $this->assertTrue($this->contact->hasValidInvitation());

        // Expired invitation
        $this->contact->invitation_expires_at = now()->subDay();
        $this->contact->save();

        $this->assertFalse($this->contact->hasValidInvitation());

        // Accepted invitation
        $this->contact->invitation_status = 'accepted';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $this->assertFalse($this->contact->hasValidInvitation());
    }

    public function test_contact_is_invitation_expired_method_works_correctly()
    {
        // Future expiration
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $this->assertFalse($this->contact->isInvitationExpired());

        // Past expiration
        $this->contact->invitation_expires_at = now()->subDay();
        $this->contact->save();

        $this->assertTrue($this->contact->isInvitationExpired());

        // No expiration set
        $this->contact->invitation_expires_at = null;
        $this->contact->save();

        $this->assertFalse($this->contact->isInvitationExpired());
    }

    public function test_invitation_sent_by_is_recorded()
    {
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $result = $this->service->sendInvitation($this->contact, $this->user);

        $this->assertTrue($result['success']);
        $this->assertEquals($this->user->id, $this->contact->fresh()->invitation_sent_by);
    }

    public function test_invitation_sent_at_is_recorded()
    {
        $this->mock(\App\Domains\Email\Services\UnifiedMailService::class)
            ->shouldReceive('sendNow')
            ->once()
            ->andReturn(true);

        $before = now()->subSecond(); // Add 1 second buffer
        $result = $this->service->sendInvitation($this->contact, $this->user);
        $after = now()->addSecond(); // Add 1 second buffer

        $this->assertTrue($result['success']);
        
        $sentAt = $this->contact->fresh()->invitation_sent_at;
        $this->assertNotNull($sentAt);
        $this->assertTrue($sentAt->between($before, $after));
    }
}
