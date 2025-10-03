<?php

namespace Database\Factories;

use App\Models\MailQueue;
use Illuminate\Database\Eloquent\Factories\Factory;

class MailQueueFactory extends Factory
{
    protected $model = MailQueue::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'uuid' => null,
            'from_email' => $this->faker->safeEmail,
            'from_name' => $this->faker->words(3, true),
            'to_email' => $this->faker->safeEmail,
            'to_name' => $this->faker->words(3, true),
            'cc' => null,
            'bcc' => null,
            'reply_to' => null,
            'subject' => null,
            'html_body' => null,
            'text_body' => null,
            'attachments' => null,
            'headers' => null,
            'template' => null,
            'template_data' => null,
            'status' => 'active',
            'priority' => null,
            'attempts' => null,
            'max_attempts' => null,
            'scheduled_at' => null,
            'sent_at' => null,
            'failed_at' => null,
            'next_retry_at' => null,
            'last_error' => null,
            'error_log' => null,
            'failure_reason' => null,
            'mailer' => null,
            'provider_response' => null,
            'tracking_token' => null,
            'opened_at' => null,
            'open_count' => null,
            'opens' => null,
            'click_count' => null,
            'clicks' => null,
            'category' => null,
            'related_type' => null,
            'tags' => null,
            'metadata' => null
        ];
    }
}
