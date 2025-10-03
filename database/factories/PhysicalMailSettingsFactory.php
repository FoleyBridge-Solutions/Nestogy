<?php

namespace Database\Factories;

use App\Models\PhysicalMailSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhysicalMailSettingsFactory extends Factory
{
    protected $model = PhysicalMailSettings::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'test_key' => $this->faker->optional()->word,
            'live_key' => $this->faker->optional()->word,
            'webhook_secret' => $this->faker->optional()->word,
            'force_test_mode' => $this->faker->optional()->word,
            'from_company_name' => $this->faker->words(3, true),
            'from_contact_name' => $this->faker->words(3, true),
            'from_address_line1' => $this->faker->optional()->word,
            'from_address_line2' => $this->faker->optional()->word,
            'from_city' => $this->faker->optional()->word,
            'from_state' => $this->faker->optional()->word,
            'from_zip' => $this->faker->optional()->word,
            'from_country' => $this->faker->optional()->word,
            'default_mailing_class' => $this->faker->optional()->word,
            'default_color_printing' => $this->faker->optional()->word,
            'default_double_sided' => $this->faker->optional()->word,
            'default_address_placement' => $this->faker->optional()->word,
            'default_size' => $this->faker->optional()->word,
            'track_costs' => $this->faker->optional()->word,
            'markup_percentage' => $this->faker->optional()->word,
            'include_tax' => $this->faker->optional()->word,
            'enable_ncoa' => $this->faker->optional()->word,
            'enable_address_verification' => $this->faker->optional()->word,
            'enable_return_envelopes' => $this->faker->optional()->word,
            'enable_bulk_mail' => $this->faker->optional()->word,
            'is_active' => $this->faker->boolean(70),
            'last_connection_test' => $this->faker->optional()->word,
            'last_connection_status' => 'active'
        ];
    }
}
