<?php

namespace Database\Factories;

use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    public function definition(): array
    {
        $type = $this->faker->randomElement(['credit_card', 'bank_account', 'digital_wallet', 'crypto']);
        
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'type' => $type,
            'provider' => $this->faker->randomElement(['stripe', 'square', 'paypal', 'plaid']),
            'provider_payment_method_id' => $this->faker->optional()->regexify('[A-Z]{2}[0-9]{10}'),
            'provider_customer_id' => $this->faker->optional()->regexify('[A-Z]{3}[0-9]{10}'),
            'token' => $this->faker->optional()->sha256,
            'fingerprint' => $this->faker->optional()->sha1,
            'name' => $this->faker->optional()->words(3, true),
            'description' => $this->faker->optional()->sentence,
            'is_default' => $this->faker->boolean(30),
            'is_active' => $this->faker->boolean(80),
            'verified' => $this->faker->boolean(70),
            'verified_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'card_brand' => $type === 'credit_card' ? $this->faker->randomElement(['visa', 'mastercard', 'amex', 'discover']) : null,
            'card_last_four' => $type === 'credit_card' ? $this->faker->numerify('####') : null,
            'card_exp_month' => $type === 'credit_card' ? $this->faker->numerify('##') : null,
            'card_exp_year' => $type === 'credit_card' ? $this->faker->numerify('####') : null,
            'card_holder_name' => $type === 'credit_card' ? $this->faker->name : null,
            'card_country' => $type === 'credit_card' ? $this->faker->countryCode : null,
            'card_funding' => $type === 'credit_card' ? $this->faker->randomElement(['credit', 'debit', 'prepaid']) : null,
            'card_checks_cvc_check' => $type === 'credit_card' ? $this->faker->optional()->boolean : null,
            'card_checks_address_line1_check' => $type === 'credit_card' ? $this->faker->optional()->boolean : null,
            'card_checks_address_postal_code_check' => $type === 'credit_card' ? $this->faker->optional()->boolean : null,
            'bank_name' => $type === 'bank_account' ? $this->faker->company : null,
            'bank_account_type' => $type === 'bank_account' ? $this->faker->randomElement(['checking', 'savings']) : null,
            'bank_account_last_four' => $type === 'bank_account' ? $this->faker->numerify('####') : null,
            'bank_routing_number_last_four' => $type === 'bank_account' ? $this->faker->numerify('####') : null,
            'bank_account_holder_type' => $type === 'bank_account' ? $this->faker->randomElement(['individual', 'company']) : null,
            'bank_account_holder_name' => $type === 'bank_account' ? $this->faker->name : null,
            'bank_country' => $type === 'bank_account' ? $this->faker->countryCode : null,
            'bank_currency' => $type === 'bank_account' ? $this->faker->currencyCode : null,
            'wallet_type' => $type === 'digital_wallet' ? $this->faker->randomElement(['paypal', 'venmo', 'apple_pay', 'google_pay']) : null,
            'wallet_email' => $type === 'digital_wallet' ? $this->faker->safeEmail : null,
            'wallet_phone' => $type === 'digital_wallet' ? $this->faker->optional()->phoneNumber : null,
            'crypto_type' => $type === 'crypto' ? $this->faker->randomElement(['bitcoin', 'ethereum', 'litecoin']) : null,
            'crypto_address' => $type === 'crypto' ? $this->faker->optional()->sha256 : null,
            'crypto_network' => $type === 'crypto' ? $this->faker->optional()->word : null,
            'billing_name' => $this->faker->optional()->name,
            'billing_email' => $this->faker->optional()->safeEmail,
            'billing_phone' => $this->faker->optional()->phoneNumber,
            'billing_address_line1' => $this->faker->optional()->streetAddress,
            'billing_address_line2' => $this->faker->optional()->secondaryAddress,
            'billing_city' => $this->faker->optional()->city,
            'billing_state' => $this->faker->optional()->stateAbbr,
            'billing_postal_code' => $this->faker->optional()->postcode,
            'billing_country' => $this->faker->optional()->countryCode,
            'security_checks' => $this->faker->optional()->passthrough(json_encode([])),
            'compliance_data' => $this->faker->optional()->passthrough(json_encode([])),
            'requires_3d_secure' => $this->faker->boolean(20),
            'risk_assessment' => $this->faker->optional()->passthrough(json_encode([])),
            'successful_payments_count' => $this->faker->numberBetween(0, 100),
            'failed_payments_count' => $this->faker->numberBetween(0, 10),
            'total_payment_amount' => $this->faker->randomFloat(2, 0, 10000),
            'last_used_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_failed_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_failure_reason' => $this->faker->optional()->sentence,
            'metadata' => json_encode([]),
            'preferences' => json_encode([]),
            'restrictions' => json_encode([]),
        ];
    }
}
