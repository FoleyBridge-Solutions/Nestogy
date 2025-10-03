<?php

namespace Database\Factories;

use App\Models\CompanyMailSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyMailSettingsFactory extends Factory
{
    protected $model = CompanyMailSettings::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'driver' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'smtp_host' => $this->faker->optional()->word,
            'smtp_port' => $this->faker->optional()->word,
            'smtp_encryption' => $this->faker->optional()->word,
            'smtp_username' => $this->faker->words(3, true),
            'smtp_password' => $this->faker->optional()->word,
            'smtp_timeout' => $this->faker->optional()->word,
            'api_key' => $this->faker->optional()->word,
            'api_secret' => $this->faker->optional()->word,
            'api_domain' => $this->faker->optional()->word,
            'ses_key' => $this->faker->optional()->word,
            'ses_secret' => $this->faker->optional()->word,
            'ses_region' => $this->faker->optional()->word,
            'mailgun_domain' => $this->faker->optional()->word,
            'mailgun_secret' => $this->faker->optional()->word,
            'mailgun_endpoint' => $this->faker->optional()->word,
            'postmark_token' => $this->faker->optional()->word,
            'sendgrid_api_key' => $this->faker->optional()->word,
            'from_email' => $this->faker->safeEmail,
            'from_name' => $this->faker->words(3, true),
            'reply_to' => $this->faker->optional()->word,
            'reply_to_email' => $this->faker->safeEmail,
            'reply_to_name' => $this->faker->words(3, true),
            'rate_limit_per_minute' => $this->faker->optional()->word,
            'rate_limit_per_hour' => $this->faker->optional()->word,
            'rate_limit_per_day' => $this->faker->optional()->word,
            'track_opens' => $this->faker->optional()->word,
            'track_clicks' => $this->faker->optional()->word,
            'auto_retry_failed' => $this->faker->optional()->word,
            'max_retry_attempts' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_test_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_test_successful' => $this->faker->optional()->word,
            'last_test_error' => $this->faker->optional()->word,
            'fallback_config' => $this->faker->optional()->word
        ];
    }
}
