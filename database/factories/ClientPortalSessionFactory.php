<?php

namespace Database\Factories;

use App\Models\ClientPortalSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientPortalSessionFactory extends Factory
{
    protected $model = ClientPortalSession::class;

    public function definition(): array
    {
        return [
            'company_id' => \App\Models\Company::factory(),
            'client_id' => \App\Models\Client::factory(),
            'session_token' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(64)),
            'refresh_token' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(64)),
            'device_id' => $this->faker->optional()->uuid,
            'device_name' => $this->faker->optional()->words(2, true),
            'device_type' => $this->faker->randomElement(['web', 'mobile', 'tablet']),
            'browser_name' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
            'browser_version' => $this->faker->numerify('##.#'),
            'os_name' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
            'os_version' => $this->faker->numerify('##.#'),
            'ip_address' => $this->faker->ipv4,
            'user_agent' => $this->faker->userAgent,
            'location_data' => $this->faker->optional()->passthrough(json_encode([
                'city' => $this->faker->city,
                'country' => $this->faker->countryCode,
            ])),
            'is_mobile' => $this->faker->boolean(30),
            'is_trusted_device' => $this->faker->boolean(50),
            'two_factor_verified' => $this->faker->boolean(60),
            'two_factor_method' => $this->faker->optional()->randomElement(['sms', 'email', 'authenticator']),
            'two_factor_verified_at' => $this->faker->optional()->dateTimeBetween('-1 hour', 'now'),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(2),
            'refresh_expires_at' => now()->addDays(7),
            'session_data' => $this->faker->optional()->passthrough(json_encode([])),
            'security_flags' => $this->faker->optional()->passthrough(json_encode([])),
            'status' => $this->faker->randomElement(['active', 'expired', 'revoked', 'suspended']),
            'revocation_reason' => $this->faker->optional()->sentence,
            'revoked_at' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
