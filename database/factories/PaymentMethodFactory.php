<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'type' => $this->faker->numberBetween(1, 5),
            'provider' => $this->faker->optional()->word,
            'token' => $this->faker->optional()->word,
            'fingerprint' => $this->faker->optional()->word,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'is_default' => $this->faker->boolean(70),
            'is_active' => $this->faker->boolean(70),
            'verified' => $this->faker->optional()->word,
            'verified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'card_brand' => $this->faker->optional()->word,
            'card_last_four' => $this->faker->optional()->word,
            'card_exp_month' => $this->faker->optional()->word,
            'card_exp_year' => $this->faker->optional()->word,
            'card_holder_name' => $this->faker->words(3, true),
            'card_country' => $this->faker->optional()->word,
            'card_funding' => $this->faker->optional()->word,
            'card_checks_cvc_check' => $this->faker->optional()->word,
            'card_checks_address_line1_check' => $this->faker->optional()->word,
            'card_checks_address_postal_code_check' => $this->faker->optional()->word,
            'bank_name' => $this->faker->words(3, true),
            'bank_account_type' => $this->faker->numberBetween(1, 5),
            'bank_account_last_four' => $this->faker->optional()->word,
            'bank_routing_number_last_four' => $this->faker->optional()->word,
            'bank_account_holder_type' => $this->faker->numberBetween(1, 5),
            'bank_account_holder_name' => $this->faker->words(3, true),
            'bank_country' => $this->faker->optional()->word,
            'bank_currency' => $this->faker->optional()->word,
            'wallet_type' => $this->faker->numberBetween(1, 5),
            'wallet_email' => $this->faker->safeEmail,
            'wallet_phone' => $this->faker->optional()->phoneNumber,
            'crypto_type' => $this->faker->numberBetween(1, 5),
            'crypto_address' => $this->faker->optional()->word,
            'crypto_network' => $this->faker->optional()->word,
            'billing_name' => $this->faker->words(3, true),
            'billing_email' => $this->faker->safeEmail,
            'billing_phone' => $this->faker->optional()->phoneNumber,
            'billing_address_line1' => $this->faker->optional()->word,
            'billing_address_line2' => $this->faker->optional()->word,
            'billing_city' => $this->faker->optional()->word,
            'billing_state' => $this->faker->optional()->word,
            'billing_postal_code' => $this->faker->word,
            'billing_country' => $this->faker->optional()->word,
            'security_checks' => $this->faker->optional()->word,
            'compliance_data' => $this->faker->optional()->word,
            'requires_3d_secure' => $this->faker->optional()->word,
            'risk_assessment' => $this->faker->optional()->word,
            'successful_payments_count' => $this->faker->optional()->word,
            'failed_payments_count' => $this->faker->optional()->word,
            'total_payment_amount' => $this->faker->randomFloat(2, 0, 10000),
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_failed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_failure_reason' => $this->faker->optional()->word,
            'metadata' => $this->faker->optional()->word,
            'preferences' => $this->faker->optional()->word,
            'restrictions' => $this->faker->optional()->word,
            'daily_limit' => $this->faker->optional()->word,
            'monthly_limit' => $this->faker->optional()->word,
            'allowed_currencies' => $this->faker->optional()->word,
            'blocked_countries' => $this->faker->optional()->word,
            'expires_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'deactivated_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'deactivation_reason' => $this->faker->optional()->word,
            'created_by' => $this->faker->optional()->word,
            'updated_by' => $this->faker->optional()->dateTimeBetween('-1 year', 'now')
        ];
    }
}
