<?php

namespace Database\Factories;

use App\Models\PhysicalMailSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

class PhysicalMailSettingsFactory extends Factory
{
    protected $model = PhysicalMailSettings::class;

    public function definition(): array
    {
        return ['company_id' => \App\Models\Company::factory(),
            'test_key' => $this->faker->optional()->randomNumber(),
            'live_key' => $this->faker->optional()->randomNumber(),
            'webhook_secret' => $this->faker->optional()->randomNumber(),
            'force_test_mode' => $this->faker->boolean(),
            'from_company_name' => $this->faker->words(3, true),
            'from_contact_name' => $this->faker->words(3, true),
            'from_address_line1' => $this->faker->optional()->randomNumber(),
            'from_address_line2' => $this->faker->optional()->randomNumber(),
            'from_city' => $this->faker->optional()->randomNumber(),
            'from_state' => $this->faker->optional()->stateAbbr(),
            'from_zip' => $this->faker->optional()->randomNumber(),
            'from_country' => $this->faker->countryCode(),
            'default_mailing_class' => $this->faker->randomElement(['usps_first_class', 'usps_standard', 'certified', 'priority']),
            'default_color_printing' => $this->faker->boolean(),
            'default_double_sided' => $this->faker->boolean(),
            'default_address_placement' => $this->faker->randomElement(['top_first_page', 'top_all_pages', 'bottom_first_page', 'custom']),
            'default_size' => $this->faker->randomElement(['us_letter', 'us_legal', 'a4', 'postcard']),
            'track_costs' => $this->faker->boolean(),
            'markup_percentage' => $this->faker->randomFloat(2, 0, 100),
            'include_tax' => $this->faker->boolean(),
            'enable_ncoa' => $this->faker->boolean(),
            'enable_address_verification' => $this->faker->boolean(),
            'enable_return_envelopes' => $this->faker->boolean(),
            'enable_bulk_mail' => $this->faker->boolean(),
            'is_active' => $this->faker->boolean(70),
            'last_connection_test' => $this->faker->optional()->dateTimeBetween('-30 days', 'now'),
            'last_connection_status' => 'active'
        ];
    }
}
