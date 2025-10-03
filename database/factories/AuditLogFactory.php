<?php

namespace Database\Factories;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'company_id' => 1,
            'event_type' => null,
            'model_type' => null,
            'action' => null,
            'old_values' => null,
            'new_values' => null,
            'metadata' => null,
            'ip_address' => null,
            'user_agent' => null,
            'request_method' => null,
            'request_url' => null,
            'request_headers' => null,
            'request_body' => null,
            'response_status' => 'active',
            'execution_time' => null,
            'severity' => null
        ];
    }
}
