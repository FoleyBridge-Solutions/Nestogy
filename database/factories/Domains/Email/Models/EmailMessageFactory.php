<?php

namespace Database\Factories\Domains\Email\Models;

use App\Domains\Email\Models\EmailAccount;
use App\Domains\Email\Models\EmailMessage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Email\Models\EmailMessage>
 */
class EmailMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = EmailMessage::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email_account_id' => EmailAccount::factory(),
            'email_folder_id' => null,
            'message_id' => $this->faker->uuid(),
            'uid' => $this->faker->randomNumber(5),
            'remote_id' => $this->faker->uuid(),
            'thread_id' => $this->faker->uuid(),
            'reply_to_message_id' => null,
            'subject' => $this->faker->sentence(),
            'from_address' => $this->faker->safeEmail(),
            'from_name' => $this->faker->name(),
            'to_addresses' => [$this->faker->safeEmail()],
            'cc_addresses' => [],
            'bcc_addresses' => [],
            'reply_to_addresses' => [],
            'body_text' => $this->faker->paragraphs(3, true),
            'body_html' => '<p>' . implode('</p><p>', $this->faker->paragraphs(3)) . '</p>',
            'preview' => $this->faker->sentence(),
            'sent_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'received_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'size_bytes' => $this->faker->numberBetween(1000, 100000),
            'priority' => $this->faker->randomElement(['normal', 'high', 'low']),
            'is_read' => false,
            'is_flagged' => false,
            'is_draft' => false,
            'is_answered' => false,
            'is_deleted' => false,
            'has_attachments' => false,
            'is_ticket_created' => false,
            'ticket_id' => null,
            'is_communication_logged' => false,
            'communication_log_id' => null,
            'headers' => [],
            'flags' => [],
            'ai_summary' => null,
            'ai_sentiment' => null,
            'ai_priority' => null,
            'ai_suggested_reply' => null,
            'ai_action_items' => null,
            'ai_analyzed_at' => null,
        ];
    }

    /**
     * Indicate that the email has been read.
     */
    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_read' => true,
        ]);
    }

    /**
     * Indicate that the email has attachments.
     */
    public function withAttachments(): static
    {
        return $this->state(fn (array $attributes) => [
            'has_attachments' => true,
        ]);
    }

    /**
     * Indicate that the email is flagged.
     */
    public function flagged(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_flagged' => true,
        ]);
    }
}
