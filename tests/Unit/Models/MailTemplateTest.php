<?php

namespace Tests\Unit\Models;

use App\Models\MailTemplate;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_mail_template_with_factory(): void
    {
        if (!class_exists('Database\\Factories\\MailTemplateFactory')) {
            $this->markTestSkipped('MailTemplateFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = MailTemplate::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(MailTemplate::class, $model);
    }

    public function test_mail_template_belongs_to_company(): void
    {
        if (!class_exists('Database\\Factories\\MailTemplateFactory')) {
            $this->markTestSkipped('MailTemplateFactory does not exist');
        }

        $company = Company::factory()->create();
        $model = MailTemplate::factory()->create(['company_id' => $company->id]);

        $this->assertInstanceOf(Company::class, $model->company);
        $this->assertEquals($company->id, $model->company->id);
    }

    public function test_mail_template_has_fillable_attributes(): void
    {
        $model = new MailTemplate();
        $fillable = $model->getFillable();

        $this->assertIsArray($fillable);
    }
}
