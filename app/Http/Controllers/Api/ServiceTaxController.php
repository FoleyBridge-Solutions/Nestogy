<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\TaxJurisdiction;
use App\Services\ServiceTaxCalculator;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ServiceTaxController extends Controller
{
    protected ?ServiceTaxCalculator $taxCalculator = null;

    protected function getTaxCalculator(): ServiceTaxCalculator
    {
        if (!$this->taxCalculator) {
            $companyId = auth()->user()->company_id ?? 1;
            $this->taxCalculator = new ServiceTaxCalculator($companyId);
        }
        return $this->taxCalculator;
    }

    /**
     * Calculate tax for a service based on customer address
     */
    public function calculateTax(Request $request): JsonResponse
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
            'customer_id' => 'nullable|exists:clients,id',
            'address' => 'nullable|array',
            'address.state' => 'nullable|string|max:2',
            'address.city' => 'nullable|string|max:255',
            'address.zip' => 'nullable|string|max:10',
            'address.country' => 'nullable|string|max:2',
            'service_type' => 'nullable|string|in:general,voip,cloud,saas,professional',
            'category_id' => 'nullable|exists:categories,id',
        ]);

        try {
            $price = $request->input('price');
            $quantity = $request->input('quantity', 1);
            $subtotal = $price * $quantity;
            
            // Get address from customer or use provided address
            $address = null;
            if ($request->filled('customer_id')) {
                $customer = Client::find($request->customer_id);
                if ($customer && $customer->company_id === auth()->user()->company_id) {
                    $address = [
                        'state' => $customer->state,
                        'city' => $customer->city,
                        'zip' => $customer->zip_code,
                        'country' => $customer->country ?? 'US',
                    ];
                }
            } elseif ($request->filled('address')) {
                $address = $request->input('address');
            }

            // If no address is provided, return zero tax
            if (!$address || !isset($address['state'])) {
                return response()->json([
                    'subtotal' => $subtotal,
                    'tax_amount' => 0,
                    'tax_rate' => 0,
                    'tax_breakdown' => [],
                    'total' => $subtotal,
                    'jurisdiction' => null,
                    'message' => 'No address provided for tax calculation'
                ]);
            }

            // Find applicable tax jurisdiction
            $jurisdiction = $this->findJurisdiction($address);
            
            // Prepare items for tax calculation
            $items = collect([
                (object)[
                    'id' => 'temp_' . uniqid(),
                    'name' => 'Service',
                    'price' => $price,
                    'quantity' => $quantity,
                    'subtotal' => $subtotal,
                    'service_type' => $request->input('service_type', 'general'),
                    'category_id' => $request->input('category_id'),
                ]
            ]);

            // Calculate taxes
            $serviceType = $request->input('service_type', 'general');
            $calculations = $this->getTaxCalculator()->calculate($items, $serviceType, $address);
            
            // Get summary
            $summary = $this->getTaxCalculator()->getTaxSummary($calculations);
            
            // Extract first (and only) item's calculation
            $calculation = $calculations[0] ?? null;
            
            return response()->json([
                'subtotal' => $subtotal,
                'tax_amount' => $calculation['total_tax_amount'] ?? 0,
                'tax_rate' => $summary['effective_tax_rate'] ?? 0,
                'tax_breakdown' => $calculation['tax_breakdown'] ?? [],
                'total' => $subtotal + ($calculation['total_tax_amount'] ?? 0),
                'jurisdiction' => $jurisdiction ? [
                    'id' => $jurisdiction->id,
                    'name' => $jurisdiction->name,
                    'type' => $jurisdiction->jurisdiction_type,
                    'state' => $jurisdiction->state_code,
                ] : null,
                'address_used' => $address,
                'service_type' => $serviceType,
            ]);

        } catch (\Exception $e) {
            Log::error('Tax calculation error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to calculate tax',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while calculating tax',
            ], 500);
        }
    }

    /**
     * Calculate taxes for multiple quote items
     */
    public function calculateQuoteTax(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.service_type' => 'nullable|string|in:voip,telecom,cloud,saas,professional,general',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.service_data' => 'nullable|array',
            'items.*.service_data.line_count' => 'nullable|integer|min:1',
            'items.*.service_data.extensions' => 'nullable|integer|min:1',
            'items.*.service_data.minutes' => 'nullable|integer|min:0',
            'customer_id' => 'nullable|exists:clients,id',
            'address' => 'nullable|array',
            'address.state' => 'nullable|string|max:2',
            'address.city' => 'nullable|string|max:255',
            'address.zip' => 'nullable|string|max:10',
            'address.country' => 'nullable|string|max:2',
        ]);

        try {
            $items = $request->input('items');
            $customerId = $request->input('customer_id');
            $providedAddress = $request->input('address');
            
            // Get address from customer or use provided address
            $address = null;
            if ($customerId) {
                $customer = Client::find($customerId);
                if ($customer && $customer->company_id === auth()->user()->company_id) {
                    $address = [
                        'state' => $customer->state,
                        'city' => $customer->city,
                        'zip' => $customer->zip_code,
                        'country' => $customer->country ?? 'US',
                    ];
                }
            } elseif ($providedAddress) {
                $address = $providedAddress;
            }

            $results = [
                'items' => [],
                'totals' => [
                    'subtotal' => 0,
                    'total_tax' => 0,
                    'total_amount' => 0,
                ],
                'tax_summary' => [],
                'address_used' => $address,
            ];

            $taxEngineRouter = app(\App\Services\TaxEngine\TaxEngineRouter::class);
            $taxEngineRouter->setCompanyId(auth()->user()->company_id);
            
            foreach ($items as $index => $item) {
                $price = $item['price'];
                $quantity = $item['quantity'] ?? 1;
                $serviceType = $item['service_type'] ?? 'general';
                $serviceData = $item['service_data'] ?? [];
                $subtotal = $price * $quantity;

                $results['totals']['subtotal'] += $subtotal;

                // If no address provided, return zero tax for this item
                if (!$address || !isset($address['state'])) {
                    $results['items'][] = [
                        'index' => $index,
                        'name' => $item['name'],
                        'subtotal' => $subtotal,
                        'tax_amount' => 0,
                        'tax_rate' => 0,
                        'tax_breakdown' => [],
                        'total' => $subtotal,
                        'service_type' => $serviceType,
                        'message' => 'No address provided for tax calculation'
                    ];
                    $results['totals']['total_amount'] += $subtotal;
                    continue;
                }

                // Calculate taxes using tax engine router
                $taxCalculation = $taxEngineRouter->calculateTaxes([
                    'base_price' => $price,
                    'quantity' => $quantity,
                    'category_id' => $item['category_id'] ?? null,
                    'category_type' => $serviceType,
                    'tax_data' => $serviceData,
                    'customer_address' => $address,
                    'customer_id' => $customerId,
                ]);

                $taxAmount = $taxCalculation['total_tax_amount'] ?? 0;
                $effectiveRate = $taxCalculation['effective_tax_rate'] ?? 0;
                $taxBreakdown = $taxCalculation['tax_breakdown'] ?? [];

                $results['items'][] = [
                    'index' => $index,
                    'name' => $item['name'],
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'tax_rate' => $effectiveRate,
                    'tax_breakdown' => $taxBreakdown,
                    'total' => $subtotal + $taxAmount,
                    'service_type' => $serviceType,
                    'engine_used' => $taxCalculation['engine_used'] ?? 'general',
                ];

                $results['totals']['total_tax'] += $taxAmount;
                $results['totals']['total_amount'] += $subtotal + $taxAmount;

                // Aggregate tax breakdown for summary
                foreach ($taxBreakdown as $tax) {
                    $taxName = $tax['tax_name'] ?? 'Unknown Tax';
                    if (!isset($results['tax_summary'][$taxName])) {
                        $results['tax_summary'][$taxName] = [
                            'tax_name' => $taxName,
                            'tax_type' => $tax['tax_type'] ?? 'unknown',
                            'authority' => $tax['authority'] ?? 'Unknown',
                            'total_amount' => 0,
                            'items_count' => 0,
                        ];
                    }
                    $results['tax_summary'][$taxName]['total_amount'] += $tax['tax_amount'] ?? 0;
                    $results['tax_summary'][$taxName]['items_count']++;
                }
            }

            // Convert tax summary to array
            $results['tax_summary'] = array_values($results['tax_summary']);

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Quote tax calculation error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to calculate quote taxes',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while calculating taxes',
            ], 500);
        }
    }

    /**
     * Get customer address for tax calculation
     */
    public function getCustomerAddress(Request $request, Client $customer): JsonResponse
    {
        // Ensure customer belongs to the authenticated user's company
        if ($customer->company_id !== auth()->user()->company_id) {
            return response()->json(['error' => 'Customer not found'], 404);
        }

        return response()->json([
            'address' => [
                'state' => $customer->state,
                'city' => $customer->city,
                'zip' => $customer->zip_code,
                'country' => $customer->country ?? 'US',
            ],
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'company_name' => $customer->company_name,
            ]
        ]);
    }

    /**
     * Find tax jurisdiction based on address
     */
    protected function findJurisdiction(array $address): ?TaxJurisdiction
    {
        $companyId = auth()->user()->company_id;
        
        // Try to find most specific jurisdiction first
        $query = TaxJurisdiction::where('company_id', $companyId)
            ->where('is_active', true);

        // Try ZIP code first (most specific)
        if (!empty($address['zip'])) {
            $jurisdiction = (clone $query)
                ->where(function ($q) use ($address) {
                    $q->where('zip_code', $address['zip'])
                      ->orWhereJsonContains('zip_codes', $address['zip']);
                })
                ->first();
            
            if ($jurisdiction) {
                return $jurisdiction;
            }
        }

        // Try city + state
        if (!empty($address['city']) && !empty($address['state'])) {
            $jurisdiction = (clone $query)
                ->where('jurisdiction_type', 'city')
                ->where('state_code', $address['state'])
                ->where(function ($q) use ($address) {
                    $q->where('name', 'like', $address['city'] . '%')
                      ->orWhere('city_name', $address['city']);
                })
                ->first();
            
            if ($jurisdiction) {
                return $jurisdiction;
            }
        }

        // Try state
        if (!empty($address['state'])) {
            $jurisdiction = (clone $query)
                ->where('jurisdiction_type', 'state')
                ->where('state_code', $address['state'])
                ->first();
            
            if ($jurisdiction) {
                return $jurisdiction;
            }
        }

        // Fall back to federal if exists
        return TaxJurisdiction::where('company_id', $companyId)
            ->where('is_active', true)
            ->where('jurisdiction_type', 'federal')
            ->first();
    }
}