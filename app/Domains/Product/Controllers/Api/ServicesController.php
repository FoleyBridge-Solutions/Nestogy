<?php

namespace App\Domains\Product\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServicesController extends Controller
{
    public function index(Request $request)
    {
        $services = Service::where('company_id', auth()->user()->company_id)
            ->with('product:id,name,base_price')
            ->select('id', 'product_id', 'service_type', 'estimated_hours', 'sla_days', 'requires_scheduling', 'has_setup_fee', 'setup_fee')
            ->get()
            ->map(function ($service) {
                return [
                    'id' => $service->id,
                    'product' => [
                        'id' => $service->product->id ?? null,
                        'name' => $service->product->name ?? 'Unknown Service',
                        'base_price' => (float) ($service->product->base_price ?? 0)
                    ],
                    'service_type' => $service->service_type ?? 'consulting',
                    'estimated_hours' => $service->estimated_hours ?? 1,
                    'sla_days' => $service->sla_days,
                    'requires_scheduling' => (bool) $service->requires_scheduling,
                    'has_setup_fee' => (bool) $service->has_setup_fee,
                    'setup_fee' => (float) ($service->setup_fee ?? 0)
                ];
            });

        return response()->json($services);
    }
}