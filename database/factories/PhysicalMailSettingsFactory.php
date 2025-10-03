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
            'test_key' => null,
            'live_key' => null,
            'webhook_secret' => null,
            'force_test_mode' => null,
            'from_company_name' => $this->faker->words(3, true),
            'from_contact_name' => $this->faker->words(3, true),
            'from_address_line1' => null,
            'from_address_line2' => null,
            'from_city' => null,
            'from_state' => null,
            'from_zip' => null,
            'from_country' => null,
            'default_mailing_class' => null,
            'default_color_printing' => null,
            'default_double_sided' => null,
            'default_address_placement' => null,
            'default_size' => null,
            'track_costs' => null,
            'markup_percentage' => null,
            'include_tax' => null,
            'enable_ncoa' => null,
            'enable_address_verification' => null,
            'enable_return_envelopes' => null,
            'enable_bulk_mail' => null,
            'is_active' => true,
            'last_connection_test' => null,
            'last_connection_status' => 'active'
        ];
    }
}
