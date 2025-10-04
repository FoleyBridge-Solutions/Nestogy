<?php

namespace Database\Factories;

use App\Models\DunningAction;
use Illuminate\Database\Eloquent\Factories\Factory;

class DunningActionFactory extends Factory
{
    protected $model = DunningAction::class;

    public function definition(): array
    {
        $scheduledAt = $this->faker->dateTimeBetween('-30 days', 'now');
        
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'action_type' => $this->faker->randomElement(['email', 'sms', 'phone_call', 'letter', 'service_suspension', 'legal_notice']),
            'status' => $this->faker->randomElement(['pending', 'scheduled', 'processing', 'sent', 'delivered', 'failed', 'bounced', 'opened', 'clicked', 'responded', 'completed', 'cancelled', 'escalated']),
            'scheduled_at' => $scheduledAt,
            'attempted_at' => $this->faker->optional()->dateTimeBetween($scheduledAt, 'now'),
            'completed_at' => $this->faker->optional()->dateTimeBetween($scheduledAt, 'now'),
            'expires_at' => $this->faker->optional()->dateTimeBetween('now', '+60 days'),
            'retry_count' => $this->faker->numberBetween(0, 3),
            'next_retry_at' => $this->faker->optional()->dateTimeBetween('now', '+7 days'),
            'recipient_email' => $this->faker->safeEmail,
            'recipient_phone' => $this->faker->optional()->phoneNumber,
            'recipient_name' => $this->faker->name,
            'message_subject' => $this->faker->sentence,
            'message_content' => $this->faker->paragraph,
            'template_used' => $this->faker->optional()->word,
            'email_message_id' => $this->faker->optional()->uuid,
            'sms_message_id' => $this->faker->optional()->uuid,
            'call_session_id' => $this->faker->optional()->uuid,
            'delivery_metadata' => json_encode([]),
            'opened' => $this->faker->boolean(30),
            'opened_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'clicked' => $this->faker->boolean(15),
            'clicked_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'response_type' => $this->faker->optional()->randomElement(['payment', 'dispute', 'promise_to_pay', 'no_response']),
            'responded_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'response_data' => json_encode([]),
            'invoice_amount' => $this->faker->randomFloat(2, 100, 10000),
            'amount_due' => $this->faker->randomFloat(2, 100, 10000),
            'late_fees' => $this->faker->randomFloat(2, 0, 500),
            'days_overdue' => $this->faker->numberBetween(0, 90),
            'settlement_offer_amount' => $this->faker->optional()->randomFloat(2, 50, 5000),
            'amount_collected' => $this->faker->optional()->randomFloat(2, 0, 10000),
            'suspended_services' => json_encode([]),
            'maintained_services' => json_encode([]),
            'suspension_effective_at' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'restoration_scheduled_at' => $this->faker->optional()->dateTimeBetween('+31 days', '+60 days'),
            'suspension_reason' => $this->faker->optional()->sentence,
            'final_notice' => $this->faker->boolean(10),
            'legal_action_threatened' => $this->faker->boolean(5),
            'compliance_flags' => json_encode([]),
            'legal_disclaimer' => $this->faker->optional()->paragraph,
            'dispute_period_active' => $this->faker->boolean(10),
            'dispute_deadline' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'escalated' => $this->faker->boolean(15),
            'escalated_to_user_id' => null,
            'escalated_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'escalation_reason' => $this->faker->optional()->sentence,
            'escalation_level' => $this->faker->optional()->numberBetween(1, 3),
            'cost_per_action' => $this->faker->optional()->randomFloat(2, 0.5, 10),
            'resulted_in_payment' => $this->faker->boolean(30),
            'roi' => $this->faker->optional()->randomFloat(2, -5, 50),
            'client_satisfaction_score' => $this->faker->optional()->numberBetween(1, 5),
            'error_message' => $this->faker->optional()->sentence,
            'error_details' => $this->faker->optional()->paragraph,
            'last_error_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'requires_manual_review' => $this->faker->boolean(10),
            'pause_sequence' => $this->faker->boolean(5),
            'pause_reason' => $this->faker->optional()->sentence,
            'sequence_resumed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'next_action_id' => $this->faker->optional()->numberBetween(1, 1000),
            'created_by' => \App\Models\User::factory(),
            'processed_by' => null,
        ];
    }
}
