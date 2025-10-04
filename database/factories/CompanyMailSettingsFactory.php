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
            'company_id' => \App\Models\Company::factory(),
            'driver' => $this->faker->randomElement(['smtp', 'ses', 'mailgun', 'postmark', 'sendgrid', 'log']),
            'is_active' => $this->faker->boolean(70),
            'smtp_host' => $this->faker->optional()->domainName,
            'smtp_port' => $this->faker->optional()->randomElement([25, 465, 587, 2525]),
            'smtp_encryption' => $this->faker->optional()->randomElement(['tls', 'ssl', null]),
            'smtp_username' => $this->faker->optional()->userName,
            'smtp_password' => $this->faker->optional()->password,
            'smtp_timeout' => $this->faker->numberBetween(10, 60),
            'api_key' => $this->faker->optional()->sha256,
            'api_secret' => $this->faker->optional()->sha256,
            'api_domain' => $this->faker->optional()->domainName,
            'ses_key' => $this->faker->optional()->regexify('[A-Z0-9]{20}'),
            'ses_secret' => $this->faker->optional()->sha256,
            'ses_region' => $this->faker->randomElement(['us-east-1', 'us-west-2', 'eu-west-1']),
            'mailgun_domain' => $this->faker->optional()->domainName,
            'mailgun_secret' => $this->faker->optional()->sha256,
            'mailgun_endpoint' => $this->faker->url,
            'postmark_token' => $this->faker->optional()->sha256,
            'sendgrid_api_key' => $this->faker->optional()->sha256,
            'from_email' => $this->faker->safeEmail,
            'from_name' => $this->faker->name,
            'reply_to' => $this->faker->optional()->safeEmail,
            'reply_to_email' => $this->faker->optional()->safeEmail,
            'reply_to_name' => $this->faker->optional()->name,
            'rate_limit_per_minute' => $this->faker->numberBetween(10, 100),
            'rate_limit_per_hour' => $this->faker->numberBetween(100, 1000),
            'rate_limit_per_day' => $this->faker->numberBetween(1000, 10000),
            'track_opens' => $this->faker->boolean,
            'track_clicks' => $this->faker->boolean,
            'auto_retry_failed' => $this->faker->boolean,
            'max_retry_attempts' => $this->faker->numberBetween(1, 5),
            'last_test_at' => $this->faker->optional()->dateTimeBetween('-1 year', 'now'),
            'last_test_successful' => $this->faker->optional()->boolean,
            'last_test_error' => $this->faker->optional()->sentence,
            'fallback_config' => $this->faker->optional()->passthrough(json_encode([])),
        ];
    }
}
