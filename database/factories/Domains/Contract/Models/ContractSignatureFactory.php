<?php

namespace Database\Factories\Domains\Contract\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Contract\Models\ContractSignature;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContractSignatureFactory extends Factory
{
    protected $model = ContractSignature::class;

    public function definition(): array
    {
        return [
            'contract_id' => Contract::factory(),
            'company_id' => Company::factory(),
            'signatory_type' => 'client',
            'signatory_name' => $this->faker->name(),
            'signatory_email' => $this->faker->safeEmail(),
            'signatory_title' => $this->faker->jobTitle(),
            'signatory_company' => $this->faker->company(),
            'signature_type' => 'electronic',
            'status' => 'pending',
            'signature_data' => null,
            'signature_hash' => null,
            'provider_reference_id' => null,
            'provider' => 'internal',
            'provider_metadata' => null,
            'envelope_id' => null,
            'recipient_id' => null,
            'ip_address' => null,
            'user_agent' => null,
            'location' => null,
            'biometric_data' => null,
            'verification_code' => null,
            'identity_verified' => false,
            'sent_at' => null,
            'viewed_at' => null,
            'signed_at' => null,
            'declined_at' => null,
            'voided_at' => null,
            'expires_at' => now()->addDays(30),
            'decline_reason' => null,
            'void_reason' => null,
            'last_reminder_sent' => null,
            'reminder_count' => 0,
            'notification_settings' => null,
            'compliance_standard' => 'ESIGN',
            'audit_trail' => [[
                'action' => 'signature_created',
                'timestamp' => now(),
                'user_id' => null,
                'data' => [],
            ]],
            'certificate_id' => null,
            'signing_order' => 1,
            'is_required' => true,
            'required_fields' => null,
            'custom_fields' => null,
            'created_by' => null,
            'processed_by' => null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    public function signed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'signed',
            'sent_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'viewed_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            'signed_at' => now(),
            'signature_hash' => $this->faker->sha256(),
            'ip_address' => $this->faker->ipv4(),
        ]);
    }

    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'declined',
            'sent_at' => $this->faker->dateTimeBetween('-1 month', '-1 week'),
            'viewed_at' => $this->faker->dateTimeBetween('-1 week', '-1 day'),
            'declined_at' => now(),
            'decline_reason' => $this->faker->sentence(),
        ]);
    }
}
