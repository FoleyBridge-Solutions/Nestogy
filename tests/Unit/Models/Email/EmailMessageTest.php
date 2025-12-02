<?php

namespace Tests\Unit\Models\Email;

use App\Domains\Company\Models\Company;
use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailFolder;
use App\Domains\Email\Models\EmailMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailMessageTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
    }

    public function test_can_create_email_message(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $message = EmailMessage::factory()->create([
            'company_id' => $this->company->id,
            'email_account_id' => $account->id,
        ]);

        $this->assertInstanceOf(EmailMessage::class, $message);
        $this->assertDatabaseHas('email_messages', [
            'id' => $message->id,
            'company_id' => $this->company->id,
        ]);
    }

    public function test_belongs_to_email_account(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $message = EmailMessage::factory()->create([
            'email_account_id' => $account->id,
        ]);

        $this->assertInstanceOf(EmailAccount::class, $message->account);
        $this->assertEquals($account->id, $message->account->id);
    }

    public function test_belongs_to_folder(): void
    {
        $account = EmailAccount::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $folder = EmailFolder::factory()->create([
            'email_account_id' => $account->id,
            'company_id' => $this->company->id,
        ]);

        $message = EmailMessage::factory()->create([
            'email_account_id' => $account->id,
            'email_folder_id' => $folder->id,
        ]);

        $this->assertInstanceOf(EmailFolder::class, $message->folder);
        $this->assertEquals($folder->id, $message->folder->id);
    }

    public function test_casts_boolean_fields(): void
    {
        $message = EmailMessage::factory()->create([
            'company_id' => $this->company->id,
            'is_read' => true,
            'is_starred' => false,
            'is_draft' => false,
        ]);

        $this->assertIsBool($message->is_read);
        $this->assertIsBool($message->is_starred);
        $this->assertIsBool($message->is_draft);
        $this->assertTrue($message->is_read);
        $this->assertFalse($message->is_starred);
    }

    public function test_casts_datetime_fields(): void
    {
        $message = EmailMessage::factory()->create([
            'company_id' => $this->company->id,
            'sent_at' => now(),
            'received_at' => now(),
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $message->sent_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $message->received_at);
    }
}
