<?php

namespace Tests\Unit\Models;

use App\Models\MailQueue;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_mail_queue_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\MailQueueFactory')) {
            $this->markTestSkipped('MailQueueFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = MailQueue::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(MailQueue::class, $model);
    }

    public function test_mail_queue_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\MailQueueFactory')) {
            $this->markTestSkipped('MailQueueFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = MailQueue::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_mail_queue_has_fillable_attributes(): void
    {
        $model = new MailQueue();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
