<?php

namespace Tests\Feature\Livewire\Clients;

use App\Domains\Client\Models\Client;
use App\Domains\Client\Models\Contact;
use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Livewire\Clients\EditContact;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\RefreshesDatabase;
use Tests\TestCase;

class EditContactPasswordTest extends TestCase
{
    use RefreshesDatabase;

    protected Company $company;
    protected User $user;
    protected Client $client;
    protected Contact $contact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create(['company_id' => $this->company->id]);
        $this->client = Client::factory()->create(['company_id' => $this->company->id]);

        $this->contact = Contact::factory()->create([
            'company_id' => $this->company->id,
            'client_id' => $this->client->id,
            'email' => 'test@example.com',
            'name' => 'Test Contact',
            'has_portal_access' => true,
            'password_hash' => null,
        ]);

        \Silber\Bouncer\BouncerFacade::scope()->to($this->company->id);
        \Silber\Bouncer\BouncerFacade::allow($this->user)->everything();
        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_set_password_via_livewire_edit_component(): void
    {
        Livewire::test(EditContact::class, ['contact' => $this->contact])
            ->set('has_portal_access', true)
            ->set('auth_method', 'password')
            ->set('password', 'NewSecurePassword123!')
            ->set('password_confirmation', 'NewSecurePassword123!')
            ->call('update');

        $this->contact = $this->contact->fresh();

        $this->assertNotNull($this->contact->password_hash);
        $this->assertTrue(Hash::check('NewSecurePassword123!', $this->contact->password_hash));
        $this->assertNotNull($this->contact->password_changed_at);
        $this->assertEquals('password', $this->contact->auth_method);
    }

    /** @test */
    public function it_can_update_existing_password(): void
    {
        // Set initial password
        $this->contact->setPassword('InitialPassword123!');
        $this->contact = $this->contact->fresh();
        $initialPasswordHash = $this->contact->password_hash;

        // Update password via Livewire
        Livewire::test(EditContact::class, ['contact' => $this->contact])
            ->set('has_portal_access', true)
            ->set('auth_method', 'password')
            ->set('password', 'UpdatedPassword456!')
            ->set('password_confirmation', 'UpdatedPassword456!')
            ->call('update');

        $this->contact = $this->contact->fresh();

        // Password hash should be different
        $this->assertNotEquals($initialPasswordHash, $this->contact->password_hash);
        $this->assertTrue(Hash::check('UpdatedPassword456!', $this->contact->password_hash));
        
        // Old password should not work
        $this->assertFalse(Hash::check('InitialPassword123!', $this->contact->password_hash));
    }

    /** @test */
    public function it_does_not_change_password_if_field_is_empty(): void
    {
        // Set initial password
        $this->contact->setPassword('ExistingPassword123!');
        $this->contact = $this->contact->fresh();
        $initialPasswordHash = $this->contact->password_hash;

        // Update contact without providing new password
        Livewire::test(EditContact::class, ['contact' => $this->contact])
            ->set('has_portal_access', true)
            ->set('auth_method', 'password')
            ->set('name', 'Updated Name')
            ->set('password', '')
            ->set('password_confirmation', '')
            ->call('update');

        $this->contact = $this->contact->fresh();

        // Password should remain unchanged
        $this->assertEquals($initialPasswordHash, $this->contact->password_hash);
        $this->assertTrue(Hash::check('ExistingPassword123!', $this->contact->password_hash));
    }

    /** @test */
    public function it_validates_password_confirmation(): void
    {
        Livewire::test(EditContact::class, ['contact' => $this->contact])
            ->set('has_portal_access', true)
            ->set('auth_method', 'password')
            ->set('password', 'Password123!')
            ->set('password_confirmation', 'DifferentPassword123!')
            ->call('update')
            ->assertHasErrors(['password']);
    }

    /** @test */
    public function contact_can_login_with_password_set_via_livewire(): void
    {
        $password = 'LoginTestPassword123!';

        // Set password via Livewire
        Livewire::test(EditContact::class, ['contact' => $this->contact])
            ->set('has_portal_access', true)
            ->set('auth_method', 'password')
            ->set('password', $password)
            ->set('password_confirmation', $password)
            ->call('update');

        $this->contact = $this->contact->fresh();

        // Test actual login functionality
        $this->assertTrue($this->contact->verifyPassword($password));
        $this->assertTrue($this->contact->canAccessPortal());
    }

    /** @test */
    public function it_updates_password_changed_at_timestamp(): void
    {
        $this->assertNull($this->contact->password_changed_at);

        Livewire::test(EditContact::class, ['contact' => $this->contact])
            ->set('has_portal_access', true)
            ->set('auth_method', 'password')
            ->set('password', 'NewPassword123!')
            ->set('password_confirmation', 'NewPassword123!')
            ->call('update');

        $this->contact = $this->contact->fresh();

        $this->assertNotNull($this->contact->password_changed_at);
        $this->assertTrue($this->contact->password_changed_at->isToday());
    }
}
