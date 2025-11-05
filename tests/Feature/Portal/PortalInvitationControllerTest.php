<?php

namespace Tests\Feature\Portal;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Contact;
use App\Domains\Company\Models\Company;
use Illuminate\Support\Facades\Hash;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class PortalInvitationControllerTest extends TestCase
{
    use RefreshesDatabase;

    protected Company $company;
    protected Client $client;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

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
    }

    public function test_show_invitation_page_with_valid_token()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->get(route('client.invitation.show', ['token' => $plainToken]));

        $response->assertStatus(200);
        $response->assertViewIs('portal.invitation.accept');
        $response->assertViewHas('contact', function ($viewContact) {
            return $viewContact->id === $this->contact->id;
        });
        $response->assertViewHas('token', $plainToken);
    }

    public function test_show_invitation_page_with_expired_token()
    {
        $plainToken = 'expired-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->subDay(); // Expired
        $this->contact->save();

        $response = $this->get(route('client.invitation.show', ['token' => $plainToken]));

        $response->assertStatus(200);
        $response->assertViewIs('portal.invitation.expired');
    }

    public function test_show_invitation_page_with_invalid_token()
    {
        $response = $this->get(route('client.invitation.show', ['token' => 'invalid-token-that-does-not-exist']));

        $response->assertStatus(200);
        $response->assertViewIs('portal.invitation.expired');
    }

    public function test_accept_invitation_with_valid_password()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $response->assertRedirect(route('client.dashboard'));
        $response->assertSessionHas('success');
        
        // Verify contact was updated
        $this->contact->refresh();
        $this->assertEquals('accepted', $this->contact->invitation_status);
        $this->assertTrue(Hash::check($password, $this->contact->password_hash));
        $this->assertNull($this->contact->invitation_token);
    }

    public function test_accept_invitation_fails_with_weak_password()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $weakPassword = 'weak';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $weakPassword,
            'password_confirmation' => $weakPassword,
        ]);

        $response->assertSessionHasErrors('password');
        
        // Verify contact was NOT updated
        $this->contact->refresh();
        $this->assertEquals('sent', $this->contact->invitation_status);
        $this->assertNull($this->contact->password_hash);
    }

    public function test_accept_invitation_fails_with_mismatched_passwords()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => 'ValidPass123',
            'password_confirmation' => 'DifferentPass456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accept_invitation_fails_with_expired_token()
    {
        $plainToken = 'expired-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->subDay();
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => 'ValidPass123',
            'password_confirmation' => 'ValidPass123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accept_invitation_requires_uppercase_letter()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => 'nouppercase123',
            'password_confirmation' => 'nouppercase123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accept_invitation_requires_lowercase_letter()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => 'NOLOWERCASE123',
            'password_confirmation' => 'NOLOWERCASE123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accept_invitation_requires_number()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => 'NoNumbersHere',
            'password_confirmation' => 'NoNumbersHere',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accept_invitation_requires_minimum_length()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => 'Short1',
            'password_confirmation' => 'Short1',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_accept_invitation_logs_in_user_after_acceptance()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        // Verify user is logged in to portal guard
        $this->assertAuthenticatedAs($this->contact, 'portal');
    }

    public function test_expired_invitation_page_shows_correct_view()
    {
        $response = $this->get(route('client.invitation.expired'));

        $response->assertStatus(200);
        $response->assertViewIs('portal.invitation.expired');
    }

    public function test_accept_invitation_sets_email_verified_at()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $this->assertNull($this->contact->email_verified_at);

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->contact->refresh();
        $this->assertNotNull($this->contact->email_verified_at);
    }

    public function test_accept_invitation_sets_password_changed_at()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $this->assertNull($this->contact->password_changed_at);

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->contact->refresh();
        $this->assertNotNull($this->contact->password_changed_at);
    }

    public function test_accept_invitation_resets_failed_login_attempts()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->failed_login_count = 3;
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->contact->refresh();
        $this->assertEquals(0, $this->contact->failed_login_count);
    }

    public function test_accept_invitation_clears_must_change_password_flag()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->must_change_password = true;
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->contact->refresh();
        $this->assertFalse($this->contact->must_change_password);
    }

    public function test_invitation_acceptance_creates_activity_log()
    {
        $plainToken = 'valid-invitation-token-12345678901234567890';
        $password = 'ValidPass123';
        
        $this->contact->invitation_token = Hash::make($plainToken);
        $this->contact->invitation_status = 'sent';
        $this->contact->invitation_expires_at = now()->addDays(3);
        $this->contact->has_portal_access = true;
        $this->contact->save();

        $response = $this->post(route('client.invitation.accept', ['token' => $plainToken]), [
            'password' => $password,
            'password_confirmation' => $password,
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Contact::class,
            'subject_id' => $this->contact->id,
            'description' => 'Portal invitation accepted and password set',
        ]);
    }
}
