<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\CompanyMailSettings;
use App\Models\MailQueue;
use App\Domains\Email\Services\UnifiedMailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

class MultiTenantMailTest extends TestCase
{
    use RefreshDatabase;

    protected UnifiedMailService $mailService;
    protected Company $company1;
    protected Company $company2;
    protected User $user1;
    protected User $user2;

    protected function setUp(): void
    {
        parent::setUp();
        
        Mail::fake();
        
        $this->mailService = app(UnifiedMailService::class);
        
        // Create two companies
        $this->company1 = Company::factory()->create(['name' => 'Company A']);
        $this->company2 = Company::factory()->create(['name' => 'Company B']);
        
        // Create users for each company
        $this->user1 = User::factory()->create(['company_id' => $this->company1->id]);
        $this->user2 = User::factory()->create(['company_id' => $this->company2->id]);
        
        // Create mail settings for each company
        CompanyMailSettings::create([
            'company_id' => $this->company1->id,
            'driver' => 'smtp',
            'smtp_host' => 'smtp.company1.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'company1@example.com',
            'smtp_password' => 'password1',
            'from_email' => 'noreply@company1.com',
            'from_name' => 'Company A',
            'is_active' => true,
        ]);
        
        CompanyMailSettings::create([
            'company_id' => $this->company2->id,
            'driver' => 'smtp',
            'smtp_host' => 'smtp.company2.com',
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => 'company2@example.com',
            'smtp_password' => 'password2',
            'from_email' => 'noreply@company2.com',
            'from_name' => 'Company B',
            'is_active' => true,
        ]);
    }

    public function test_mail_queue_respects_company_isolation()
    {
        // Act as user1 from company1
        $this->actingAs($this->user1);
        
        // Queue an email for company1
        $mail1 = $this->mailService->queue([
            'company_id' => $this->company1->id,
            'to_email' => 'customer1@example.com',
            'subject' => 'Email from Company A',
            'html_body' => '<p>This is from Company A</p>',
        ]);
        
        // Act as user2 from company2
        $this->actingAs($this->user2);
        
        // Queue an email for company2
        $mail2 = $this->mailService->queue([
            'company_id' => $this->company2->id,
            'to_email' => 'customer2@example.com',
            'subject' => 'Email from Company B',
            'html_body' => '<p>This is from Company B</p>',
        ]);
        
        // Verify isolation
        $this->assertEquals($this->company1->id, $mail1->company_id);
        $this->assertEquals($this->company2->id, $mail2->company_id);
        
        // Verify company1 can only see its own emails
        $this->actingAs($this->user1);
        $company1Mails = MailQueue::all(); // Should be filtered by BelongsToCompany trait
        $this->assertCount(1, $company1Mails);
        $this->assertEquals($mail1->id, $company1Mails->first()->id);
        
        // Verify company2 can only see its own emails
        $this->actingAs($this->user2);
        $company2Mails = MailQueue::all(); // Should be filtered by BelongsToCompany trait
        $this->assertCount(1, $company2Mails);
        $this->assertEquals($mail2->id, $company2Mails->first()->id);
    }

    public function test_mail_uses_company_specific_settings()
    {
        // Act as user1
        $this->actingAs($this->user1);
        
        // Queue and send an email for company1
        $mail1 = $this->mailService->queue([
            'company_id' => $this->company1->id,
            'to_email' => 'customer@example.com',
            'subject' => 'Test Email',
            'html_body' => '<p>Test</p>',
        ]);
        
        // Check that the from email is set correctly
        $this->assertEquals('noreply@company1.com', $mail1->from_email);
        $this->assertEquals('Company A', $mail1->from_name);
        
        // Act as user2
        $this->actingAs($this->user2);
        
        // Queue and send an email for company2
        $mail2 = $this->mailService->queue([
            'company_id' => $this->company2->id,
            'to_email' => 'customer@example.com',
            'subject' => 'Test Email',
            'html_body' => '<p>Test</p>',
        ]);
        
        // Check that the from email is set correctly
        $this->assertEquals('noreply@company2.com', $mail2->from_email);
        $this->assertEquals('Company B', $mail2->from_name);
    }

    public function test_mail_settings_controller_updates_company_settings()
    {
        $this->actingAs($this->user1);
        
        // Update mail settings
        $response = $this->put(route('settings.mail.update'), [
            'driver' => 'mailgun',
            'api_key' => 'mg_test_key_123',
            'api_domain' => 'mg.company1.com',
            'from_name' => 'Company A Updated',
            'from_email' => 'hello@company1.com',
            'reply_to' => 'support@company1.com',
        ]);
        
        $response->assertRedirect(route('settings.mail.index'));
        
        // Verify settings were updated
        $settings = CompanyMailSettings::where('company_id', $this->company1->id)->first();
        $this->assertEquals('mailgun', $settings->driver);
        $this->assertEquals('Company A Updated', $settings->from_name);
        $this->assertEquals('hello@company1.com', $settings->from_email);
        $this->assertEquals('support@company1.com', $settings->reply_to);
        
        // Verify encryption of API key
        $this->assertNotEquals('mg_test_key_123', $settings->api_key);
        $this->assertEquals('mg_test_key_123', $settings->api_key_decrypted);
    }

    public function test_mail_queue_statistics_are_company_specific()
    {
        // Create emails for company1
        $this->actingAs($this->user1);
        MailQueue::factory()->count(3)->create([
            'company_id' => $this->company1->id,
            'status' => MailQueue::STATUS_SENT,
        ]);
        MailQueue::factory()->count(2)->create([
            'company_id' => $this->company1->id,
            'status' => MailQueue::STATUS_FAILED,
        ]);
        
        // Create emails for company2
        $this->actingAs($this->user2);
        MailQueue::factory()->count(5)->create([
            'company_id' => $this->company2->id,
            'status' => MailQueue::STATUS_SENT,
        ]);
        MailQueue::factory()->count(1)->create([
            'company_id' => $this->company2->id,
            'status' => MailQueue::STATUS_PENDING,
        ]);
        
        // Check company1 statistics
        $this->actingAs($this->user1);
        $response = $this->get(route('mail-queue.index'));
        $response->assertOk();
        
        $stats = $response->viewData('stats');
        $this->assertEquals(5, $stats['total']); // 3 sent + 2 failed
        $this->assertEquals(3, $stats['sent']);
        $this->assertEquals(2, $stats['failed']);
        $this->assertEquals(0, $stats['pending']);
        
        // Check company2 statistics
        $this->actingAs($this->user2);
        $response = $this->get(route('mail-queue.index'));
        $response->assertOk();
        
        $stats = $response->viewData('stats');
        $this->assertEquals(6, $stats['total']); // 5 sent + 1 pending
        $this->assertEquals(5, $stats['sent']);
        $this->assertEquals(0, $stats['failed']);
        $this->assertEquals(1, $stats['pending']);
    }
}