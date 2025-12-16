<?php

namespace Database\Factories\Domains\Email\Models;

use App\Domains\Company\Models\Company;
use App\Domains\Core\Models\User;
use App\Domains\Email\Models\EmailAccount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Domains\Email\Models\EmailAccount>
 */
class EmailAccountFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = EmailAccount::class;

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
            'name' => $this->faker->words(3, true),
            'email_address' => $this->faker->unique()->safeEmail(),
            'provider' => $this->faker->randomElement(['gmail', 'outlook', 'imap']),
            'connection_type' => $this->faker->randomElement(['imap', 'oauth']),
            'oauth_provider' => null,
            'imap_host' => $this->faker->domainName(),
            'imap_port' => 993,
            'imap_encryption' => 'ssl',
            'imap_username' => $this->faker->userName(),
            'imap_password' => encrypt($this->faker->password()),
            'imap_validate_cert' => true,
            'smtp_host' => 'smtp.' . $this->faker->domainName(),
            'smtp_port' => 587,
            'smtp_encryption' => 'tls',
            'smtp_username' => $this->faker->userName(),
            'smtp_password' => encrypt($this->faker->password()),
            'oauth_access_token' => null,
            'oauth_refresh_token' => null,
            'oauth_expires_at' => null,
            'oauth_token_expires_at' => null,
            'oauth_scopes' => null,
            'is_default' => false,
            'is_active' => true,
            'sync_interval_minutes' => 15,
            'last_synced_at' => null,
            'sync_error' => null,
            'auto_create_tickets' => false,
            'auto_log_communications' => true,
            'filters' => null,
        ];
    }

    /**
     * Indicate that the email account uses OAuth.
     */
    public function oauth(): static
    {
        return $this->state(fn (array $attributes) => [
            'connection_type' => 'oauth',
            'oauth_provider' => $this->faker->randomElement(['microsoft365', 'google_workspace']),
            'oauth_access_token' => $this->faker->uuid(),
            'oauth_refresh_token' => $this->faker->uuid(),
            'oauth_expires_at' => now()->addHours(1),
            'imap_password' => null,
            'smtp_password' => null,
        ]);
    }

    /**
     * Indicate that the email account is the default.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Indicate that the email account is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
