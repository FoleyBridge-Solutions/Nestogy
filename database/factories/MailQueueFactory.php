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
            'company_id' => \App\Models\Company::factory(),
            'uuid' => $this->faker->uuid,
            'from_email' => $this->faker->safeEmail,
            'from_name' => $this->faker->name,
            'to_email' => $this->faker->safeEmail,
            'to_name' => $this->faker->name,
            'cc' => $this->faker->optional()->safeEmail,
            'bcc' => $this->faker->optional()->safeEmail,
            'reply_to' => $this->faker->optional()->safeEmail,
            'subject' => $this->faker->sentence,
            'html_body' => $this->faker->optional()->randomHtml(),
            'text_body' => $this->faker->optional()->text,
            'attachments' => $this->faker->optional()->passthrough(json_encode([])),
            'headers' => $this->faker->optional()->passthrough(json_encode([])),
            'template' => $this->faker->optional()->word,
            'template_data' => $this->faker->optional()->passthrough(json_encode([])),
            'status' => $this->faker->randomElement(['pending', 'processing', 'sent', 'failed', 'bounced', 'complained', 'cancelled']),
            'priority' => $this->faker->randomElement(['low', 'normal', 'high', 'critical']),
            'attempts' => $this->faker->numberBetween(0, 3),
            'max_attempts' => $this->faker->numberBetween(3, 5),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('-1 day', '+7 days'),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'failed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'next_retry_at' => $this->faker->optional()->dateTimeBetween('now', '+1 day'),
            'last_error' => $this->faker->optional()->sentence,
            'error_log' => $this->faker->optional()->passthrough(json_encode([])),
            'failure_reason' => $this->faker->optional()->sentence,
            'mailer' => $this->faker->randomElement(['smtp', 'mailgun', 'sendgrid']),
            'provider_response' => $this->faker->optional()->passthrough(json_encode([])),
            'tracking_token' => $this->faker->optional()->uuid,
            'opened_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'open_count' => $this->faker->numberBetween(0, 10),
            'opens' => $this->faker->optional()->passthrough(json_encode([])),
            'click_count' => $this->faker->numberBetween(0, 5),
            'clicks' => $this->faker->optional()->passthrough(json_encode([])),
            'category' => $this->faker->optional()->word,
            'related_type' => $this->faker->optional()->randomElement([\App\Models\Client::class, \App\Models\Invoice::class]),
            'tags' => $this->faker->optional()->passthrough(json_encode([])),
            'metadata' => json_encode([]),
        ];
    }
}
