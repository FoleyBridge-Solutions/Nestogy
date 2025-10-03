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
            'driver' => null,
            'is_active' => true,
            'smtp_host' => null,
            'smtp_port' => null,
            'smtp_encryption' => null,
            'smtp_username' => $this->faker->words(3, true),
            'smtp_password' => null,
            'smtp_timeout' => null,
            'api_key' => null,
            'api_secret' => null,
            'api_domain' => null,
            'ses_key' => null,
            'ses_secret' => null,
            'ses_region' => null,
            'mailgun_domain' => null,
            'mailgun_secret' => null,
            'mailgun_endpoint' => null,
            'postmark_token' => null,
            'sendgrid_api_key' => null,
            'from_email' => $this->faker->safeEmail,
            'from_name' => $this->faker->words(3, true),
            'reply_to' => null,
            'reply_to_email' => $this->faker->safeEmail,
            'reply_to_name' => $this->faker->words(3, true),
            'rate_limit_per_minute' => null,
            'rate_limit_per_hour' => null,
            'rate_limit_per_day' => null,
            'track_opens' => null,
            'track_clicks' => null,
            'auto_retry_failed' => null,
            'max_retry_attempts' => null,
            'last_test_at' => null,
            'last_test_successful' => null,
            'last_test_error' => null,
            'fallback_config' => null
        ];
    }
}
