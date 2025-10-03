<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'service_type' => null,
            'estimated_hours' => null,
            'sla_days' => null,
            'response_time_hours' => null,
            'resolution_time_hours' => null,
            'deliverables' => null,
            'dependencies' => null,
            'requirements' => null,
            'requires_scheduling' => null,
            'min_notice_hours' => null,
            'duration_minutes' => null,
            'availability_schedule' => null,
            'required_skills' => null,
            'required_resources' => null,
            'has_setup_fee' => null,
            'setup_fee' => null,
            'has_cancellation_fee' => null,
            'cancellation_fee' => null,
            'cancellation_notice_hours' => null,
            'minimum_commitment_months' => null,
            'maximum_duration_months' => null,
            'auto_renew' => null,
            'renewal_notice_days' => null
        ];
    }
}
