<?php

namespace Database\Factories\Domains\Security\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Security\Models\TrustedDevice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Security\Models\TrustedDevice>
 */
class TrustedDeviceFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = TrustedDevice::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'device_fingerprint' => [
                'browser' => $this->faker->randomElement(['Chrome', 'Firefox', 'Safari', 'Edge']),
                'browser_version' => $this->faker->numerify('###.#'),
                'os' => $this->faker->randomElement(['Windows', 'macOS', 'Linux', 'iOS', 'Android']),
                'os_version' => $this->faker->numerify('##.#'),
                'screen_resolution' => $this->faker->randomElement(['1920x1080', '2560x1440', '1366x768']),
                'timezone' => $this->faker->timezone(),
                'device_type' => $this->faker->randomElement(['desktop', 'mobile', 'tablet']),
            ],
            'device_name' => $this->faker->optional()->randomElement([
                'John\'s iPhone',
                'Work Laptop',
                'Home Desktop',
                'iPad Pro',
            ]),
            'ip_address' => $this->faker->ipv4(),
            'location_data' => [
                'city' => $this->faker->city(),
                'region' => $this->faker->state(),
                'country' => $this->faker->country(),
                'country_code' => $this->faker->countryCode(),
                'latitude' => $this->faker->latitude(),
                'longitude' => $this->faker->longitude(),
            ],
            'user_agent' => $this->faker->userAgent(),
            'trust_level' => $this->faker->randomElement([25, 50, 75, 100]),
            'last_used_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'expires_at' => $this->faker->dateTimeBetween('now', '+1 year'),
            'is_active' => true,
            'verification_method' => $this->faker->randomElement(['email', 'sms', 'manual']),
            'created_from_suspicious_login' => false,
        ];
    }

    /**
     * Indicate that the device is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the device is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => $this->faker->dateTimeBetween('-1 year', '-1 day'),
        ]);
    }

    /**
     * Indicate that the device has high trust.
     */
    public function highTrust(): static
    {
        return $this->state(fn (array $attributes) => [
            'trust_level' => 100,
        ]);
    }
}
