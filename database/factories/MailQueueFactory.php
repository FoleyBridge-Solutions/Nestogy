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
            'uuid' => $this->faker->optional()->word,
            'from_email' => $this->faker->safeEmail,
            'from_name' => $this->faker->words(3, true),
            'to_email' => $this->faker->safeEmail,
            'to_name' => $this->faker->words(3, true),
            'cc' => $this->faker->optional()->word,
            'bcc' => $this->faker->optional()->word,
            'reply_to' => $this->faker->optional()->word,
            'subject' => $this->faker->optional()->word,
            'html_body' => $this->faker->optional()->word,
            'text_body' => $this->faker->optional()->word,
            'attachments' => $this->faker->optional()->word,
            'headers' => $this->faker->optional()->word,
            'template' => $this->faker->optional()->word,
            'template_data' => $this->faker->optional()->word,
            'status' => 'active',
            'priority' => $this->faker->optional()->word,
            'attempts' => $this->faker->optional()->word,
            'max_attempts' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'scheduled_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'sent_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'failed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'next_retry_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_error' => $this->faker->optional()->word,
            'error_log' => $this->faker->optional()->word,
            'failure_reason' => $this->faker->optional()->word,
            'mailer' => $this->faker->optional()->word,
            'provider_response' => $this->faker->optional()->word,
            'tracking_token' => $this->faker->optional()->word,
            'opened_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'open_count' => $this->faker->optional()->word,
            'opens' => $this->faker->optional()->word,
            'click_count' => $this->faker->optional()->word,
            'clicks' => $this->faker->optional()->word,
            'category' => $this->faker->optional()->word,
            'related_type' => $this->faker->numberBetween(1, 5),
            'tags' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word
        ];
    }
}
