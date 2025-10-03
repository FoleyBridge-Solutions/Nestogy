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
            'type' => null,
            'provider' => null,
            'token' => null,
            'fingerprint' => null,
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->sentence,
            'is_default' => true,
            'is_active' => true,
            'verified' => null,
            'verified_at' => null,
            'card_brand' => null,
            'card_last_four' => null,
            'card_exp_month' => null,
            'card_exp_year' => null,
            'card_holder_name' => $this->faker->words(3, true),
            'card_country' => null,
            'card_funding' => null,
            'card_checks_cvc_check' => null,
            'card_checks_address_line1_check' => null,
            'card_checks_address_postal_code_check' => null,
            'bank_name' => $this->faker->words(3, true),
            'bank_account_type' => null,
            'bank_account_last_four' => null,
            'bank_routing_number_last_four' => null,
            'bank_account_holder_type' => null,
            'bank_account_holder_name' => $this->faker->words(3, true),
            'bank_country' => null,
            'bank_currency' => null,
            'wallet_type' => null,
            'wallet_email' => $this->faker->safeEmail,
            'wallet_phone' => null,
            'crypto_type' => null,
            'crypto_address' => null,
            'crypto_network' => null,
            'billing_name' => $this->faker->words(3, true),
            'billing_email' => $this->faker->safeEmail,
            'billing_phone' => null,
            'billing_address_line1' => null,
            'billing_address_line2' => null,
            'billing_city' => null,
            'billing_state' => null,
            'billing_postal_code' => null,
            'billing_country' => null,
            'security_checks' => null,
            'compliance_data' => null,
            'requires_3d_secure' => null,
            'risk_assessment' => null,
            'successful_payments_count' => null,
            'failed_payments_count' => null,
            'total_payment_amount' => null,
            'last_used_at' => null,
            'last_failed_at' => null,
            'last_failure_reason' => null,
            'metadata' => null,
            'preferences' => null,
            'restrictions' => null,
            'daily_limit' => null,
            'monthly_limit' => null,
            'allowed_currencies' => null,
            'blocked_countries' => null,
            'expires_at' => null,
            'deactivated_at' => null,
            'deactivation_reason' => null,
            'created_by' => null,
            'updated_by' => null
        ];
    }
}
