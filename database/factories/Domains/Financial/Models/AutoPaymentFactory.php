<?php

namespace Database\Factories\Domains\Financial\Models;

use App\Domains\Financial\Models\AutoPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class AutoPaymentFactory extends Factory
{
    protected $model = AutoPayment::class;

    public function definition(): array
    {
        return [
            'company_id' => 1, // Don't create new companies - use existing
            'client_id' => \App\Domains\Client\Models\Client::factory(),
            'payment_method_id' => \App\Domains\Financial\Models\PaymentMethod::factory(),
            'name' => $this->faker->words(3, true),
            'type' => 'invoice_auto_pay',
            'is_active' => true,
            'trigger_type' => 'invoice_due',
            'trigger_days_offset' => 0,
            'trigger_time' => '09:00:00',
            'status' => 'active',
            'currency_code' => 'USD',
            'next_processing_date' => now()->addDays(30),
        ];
    }
}
