<?php

namespace Tests\Unit\Models\Email;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailFolder;
use App\Domains\Email\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAccountTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);
        $this->actingAs($this->user);
    }

    public function test_can_create_email_account(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(EmailAccount::class, $account);
        $this->assertDatabaseHas('email_accounts', [
            'id' => $account->id,
            'company_id' => $this->company->id,
            'user_id' => $this->user->id,
        ]);
    }

    public function test_belongs_to_company(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $this->assertInstanceOf(Company::class, $account->company);
        $this->assertEquals($this->company->id, $account->company->id);
    }

    public function test_belongs_to_user(): void
    {
        $account = EmailAccount::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $account->user);
        $this->assertEquals($this->user->id, $account->user->id);
    }

    public function test_has_many_folders(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        EmailFolder::factory()->count(3)->create([
            'email_account_id' => $account->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertCount(3, $account->folders);
    }

    public function test_has_many_messages(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        EmailMessage::factory()->count(5)->create([
            'email_account_id' => $account->id,
            'company_id' => $this->company->id,
        ]);

        $this->assertCount(5, $account->messages);
    }

    public function test_password_fields_are_hidden(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $array = $account->toArray();

        $this->assertArrayNotHasKey('imap_password', $array);
        $this->assertArrayNotHasKey('smtp_password', $array);
        $this->assertArrayNotHasKey('oauth_access_token', $array);
        $this->assertArrayNotHasKey('oauth_refresh_token', $array);
    }

    public function test_casts_boolean_fields(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
            'is_active' => true,
            'is_default' => false,
            'auto_create_tickets' => true,
            'auto_log_communications' => false,
        ]);

        $this->assertIsBool($account->is_active);
        $this->assertIsBool($account->is_default);
        $this->assertIsBool($account->auto_create_tickets);
        $this->assertIsBool($account->auto_log_communications);
        $this->assertTrue($account->is_active);
        $this->assertFalse($account->is_default);
    }

    public function test_casts_array_fields(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
            'oauth_scopes' => ['email', 'calendar'],
            'filters' => ['spam' => false],
        ]);

        $this->assertIsArray($account->oauth_scopes);
        $this->assertIsArray($account->filters);
        $this->assertEquals(['email', 'calendar'], $account->oauth_scopes);
    }

    public function test_casts_datetime_fields(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
            'last_synced_at' => now(),
            'oauth_expires_at' => now()->addHour(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->last_synced_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $account->oauth_expires_at);
    }
}
