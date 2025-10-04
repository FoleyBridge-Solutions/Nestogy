<?php

namespace Database\Factories;

use App\Models\CollectionNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class CollectionNoteFactory extends Factory
{
    protected $model = CollectionNote::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'note_type' => 'general',
            'priority' => 'normal',
            'visibility' => 'internal',
            'subject' => $this->faker->sentence,
            'content' => $this->faker->paragraph,
            'metadata' => json_encode([]),
            'tags' => json_encode([]),
            'communication_method' => $this->faker->optional()->randomElement(['phone', 'email', 'sms', 'in_person', 'video_call', 'letter', 'portal']),
            'contact_person' => $this->faker->optional()->name,
            'contact_phone' => $this->faker->optional()->phoneNumber,
            'contact_email' => $this->faker->optional()->safeEmail,
            'contact_datetime' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'call_duration_seconds' => $this->faker->optional()->numberBetween(30, 1800),
            'outcome' => $this->faker->optional()->randomElement(['successful', 'unsuccessful', 'voicemail', 'no_answer', 'wrong_number', 'promise_to_pay', 'dispute', 'payment_made', 'escalated']),
            'outcome_details' => $this->faker->optional()->sentence,
            'contains_promise_to_pay' => $this->faker->boolean(20),
            'promised_amount' => $this->faker->optional()->randomFloat(2, 50, 5000),
            'promised_payment_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'promise_kept' => $this->faker->optional()->boolean,
            'promise_followed_up_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'contains_dispute' => $this->faker->boolean(10),
            'dispute_reason' => $this->faker->optional()->sentence,
            'disputed_amount' => $this->faker->optional()->randomFloat(2, 50, 5000),
            'dispute_status' => $this->faker->optional()->randomElement(['pending', 'investigating', 'resolved']),
            'dispute_deadline' => $this->faker->optional()->dateTimeBetween('now', '+60 days'),
            'requires_followup' => $this->faker->boolean(30),
            'followup_date' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'followup_time' => $this->faker->optional()->dateTimeBetween('now', '+30 days'),
            'followup_type' => $this->faker->optional()->randomElement(['call', 'email', 'sms', 'letter']),
            'followup_instructions' => $this->faker->optional()->sentence,
            'followup_completed' => $this->faker->boolean(40),
            'followup_completed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'legally_significant' => $this->faker->boolean(10),
            'compliance_sensitive' => $this->faker->boolean(15),
            'compliance_flags' => json_encode([]),
            'attorney_review_required' => $this->faker->boolean(5),
            'attorney_reviewed' => $this->faker->boolean(50),
            'reviewed_by_attorney_id' => null,
            'attorney_reviewed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'client_mood' => $this->faker->optional()->randomElement(['cooperative', 'neutral', 'frustrated', 'angry']),
            'satisfaction_rating' => $this->faker->optional()->numberBetween(1, 5),
            'escalation_risk' => $this->faker->optional()->randomElement(['low', 'medium', 'high']),
            'relationship_notes' => $this->faker->optional()->sentence,
            'invoice_balance_at_time' => $this->faker->optional()->randomFloat(2, 100, 10000),
            'days_overdue_at_time' => $this->faker->optional()->numberBetween(0, 90),
            'total_account_balance' => $this->faker->optional()->randomFloat(2, 100, 20000),
            'payment_history_summary' => json_encode([]),
            'attachments' => json_encode([]),
            'related_documents' => json_encode([]),
            'external_reference' => $this->faker->optional()->regexify('[A-Z]{3}[0-9]{6}'),
            'billable_time' => $this->faker->boolean(20),
            'time_spent_minutes' => $this->faker->optional()->numberBetween(5, 120),
            'hourly_rate' => $this->faker->optional()->randomFloat(2, 50, 200),
            'billable_amount' => $this->faker->optional()->randomFloat(2, 10, 500),
            'quality_reviewed' => $this->faker->boolean(30),
            'quality_reviewed_by' => null,
            'quality_reviewed_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'quality_score' => $this->faker->optional()->numberBetween(1, 10),
            'quality_feedback' => $this->faker->optional()->sentence,
            'flagged_for_review' => $this->faker->boolean(10),
            'created_by' => \App\Models\User::factory(),
            'updated_by' => null,
        ];
    }
}
