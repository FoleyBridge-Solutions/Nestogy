<?php

namespace App\Domains\Financial\Controllers\Api;

use App\Domains\Financial\Services\TaxEngine\TaxEngineRouter;
use App\Domains\Financial\Services\TaxEngine\TaxProfileService;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Unified Tax Engine API Controller
 *
 * Provides comprehensive tax calculation endpoints that automatically
 * route to appropriate tax engines based on product/service category.
 */
class TaxEngineController extends Controller
{
    protected TaxEngineRouter $taxEngine;

    protected TaxProfileService $profileService;

    public function __construct()
    {
        $companyId = auth()->user()->company_id ?? 1;
        $this->taxEngine = new TaxEngineRouter($companyId);
        $this->profileService = new TaxProfileService($companyId);
    }

    /**
     * Calculate comprehensive taxes for any product/service type
     */
    public function calculateTax(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                // Base pricing
                'base_price' => 'required|numeric|min:0',
                'quantity' => 'nullable|integer|min:1',

                // Category identification
                'category_id' => 'nullable|exists:categories,id',
                'category_type' => 'nullable|string',
                'product_id' => 'nullable|exists:products,id',

                // Customer/Address
                'customer_id' => 'nullable|exists:clients,id',
                'customer_address' => 'nullable|array',
                'customer_address.state' => 'nullable|string|max:2',
                'customer_address.city' => 'nullable|string|max:255',
                'customer_address.zip' => 'nullable|string|max:10',
                'customer_address.country' => 'nullable|string|max:2',

                // Category-specific tax data
                'tax_data' => 'nullable|array',
                'tax_data.line_count' => 'nullable|integer|min:1',
                'tax_data.minutes' => 'nullable|integer|min:0',
                'tax_data.extensions' => 'nullable|integer|min:0',
                'tax_data.data_usage' => 'nullable|numeric|min:0',
                'tax_data.storage_amount' => 'nullable|numeric|min:0',
                'tax_data.user_count' => 'nullable|integer|min:1',
                'tax_data.weight' => 'nullable|numeric|min:0',
                'tax_data.dimensions' => 'nullable|array',
                'tax_data.hours' => 'nullable|numeric|min:0',
                'tax_data.service_location' => 'nullable|array',
                'tax_data.service_type' => 'nullable|string',
            ]);

            // Get customer address if customer_id provided
            $address = $validated['customer_address'] ?? null;
            if (! $address && isset($validated['customer_id'])) {
                $customer = Client::find($validated['customer_id']);
                if ($customer && $customer->company_id === auth()->user()->company_id) {
                    $address = [
                        'state' => $customer->state,
                        'city' => $customer->city,
                        'zip' => $customer->zip_code,
                        'country' => $customer->country ?? 'US',
                    ];
                }
            }

            // Calculate taxes using the router
            $calculation = $this->taxEngine->calculateTaxes([
                'base_price' => $validated['base_price'],
                'quantity' => $validated['quantity'] ?? 1,
                'category_id' => $validated['category_id'] ?? null,
                'category_type' => $validated['category_type'] ?? null,
                'tax_data' => $validated['tax_data'] ?? [],
                'customer_address' => $address,
                'customer_id' => $validated['customer_id'] ?? null,
            ]);

            // Format response
            return response()->json([
                'success' => true,
                'data' => [
                    'base_price' => $validated['base_price'],
                    'quantity' => $validated['quantity'] ?? 1,
                    'subtotal' => $calculation['base_amount'],
                    'tax_amount' => $calculation['total_tax_amount'],
                    'tax_rate' => $calculation['effective_tax_rate'] ?? 0,
                    'tax_breakdown' => $calculation['tax_breakdown'] ?? [],
                    'total' => $calculation['final_amount'],
                    'engine_used' => $calculation['engine_used'] ?? 'general',
                    'tax_profile' => $calculation['tax_profile'] ?? null,
                    'jurisdictions' => $calculation['jurisdictions'] ?? [],
                    'address_used' => $address,
                ],
                'calculation_id' => uniqid('calc_'),
                'calculated_at' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Tax calculation error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Tax calculation failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while calculating tax',
            ], 500);
        }
    }

    /**
     * Calculate taxes for multiple items in bulk (for quote/invoice creation)
     */
    public function calculateBulkTax(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'items' => 'required|array|min:1|max:100', // Limit to 100 items per request
                'items.*.base_price' => 'required|numeric|min:0',
                'items.*.quantity' => 'nullable|integer|min:1',
                'items.*.name' => 'nullable|string|max:255',
                'items.*.category_id' => 'nullable|exists:categories,id',
                'items.*.category_type' => 'nullable|string',
                'items.*.product_id' => 'nullable|exists:products,id',
                'items.*.tax_data' => 'nullable|array',

                // Global settings for all items
                'customer_id' => 'nullable|exists:clients,id',
                'customer_address' => 'nullable|array',
                'calculation_type' => 'nullable|string|in:preview,final,estimate',
            ]);

            // Get customer address once for all items
            $address = $validated['customer_address'] ?? null;
            if (! $address && isset($validated['customer_id'])) {
                $customer = Client::find($validated['customer_id']);
                if ($customer && $customer->company_id === auth()->user()->company_id) {
                    $address = [
                        'state' => $customer->state,
                        'city' => $customer->city,
                        'zip' => $customer->zip_code,
                        'country' => $customer->country ?? 'US',
                    ];
                }
            }

            // Prepare items for bulk calculation
            $bulkItems = [];
            foreach ($validated['items'] as $index => $item) {
                $bulkItems[] = [
                    'base_price' => $item['base_price'],
                    'quantity' => $item['quantity'] ?? 1,
                    'name' => $item['name'] ?? 'Item '.($index + 1),
                    'category_id' => $item['category_id'] ?? null,
                    'category_type' => $item['category_type'] ?? null,
                    'tax_data' => $item['tax_data'] ?? [],
                    'customer_address' => $address,
                    'customer_id' => $validated['customer_id'] ?? null,
                ];
            }

            // Perform bulk calculation
            $calculationType = $validated['calculation_type'] ?? 'preview';
            $results = $this->taxEngine->calculateBulkTaxes($bulkItems, $calculationType);

            // Calculate totals
            $totalSubtotal = 0;
            $totalTax = 0;
            $totalAmount = 0;

            foreach ($results['bulk_results'] as $result) {
                $totalSubtotal += $result['base_amount'] ?? 0;
                $totalTax += $result['total_tax_amount'] ?? 0;
                $totalAmount += $result['final_amount'] ?? 0;
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $results['bulk_results'],
                    'summary' => [
                        'total_items' => $results['total_items'],
                        'subtotal' => $totalSubtotal,
                        'total_tax' => $totalTax,
                        'total_amount' => $totalAmount,
                        'effective_tax_rate' => $totalSubtotal > 0 ? round(($totalTax / $totalSubtotal) * 100, 4) : 0,
                    ],
                    'performance' => [
                        'calculation_time_ms' => $results['calculation_time_ms'],
                        'items_per_second' => $results['items_per_second'],
                        'engine_breakdown' => $results['engine_breakdown'],
                    ],
                    'address_used' => $address,
                ],
                'calculation_id' => uniqid('bulk_'),
                'calculated_at' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Bulk tax calculation error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Bulk tax calculation failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while calculating taxes',
            ], 500);
        }
    }

    /**
     * Preview taxes for a quote without saving
     */
    public function previewQuoteTax(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quote_data' => 'required|array',
                'quote_data.client_id' => 'required|exists:clients,id',
                'quote_data.items' => 'required|array|min:1',
                'quote_data.items.*.name' => 'required|string|max:255',
                'quote_data.items.*.quantity' => 'required|numeric|min:0.01',
                'quote_data.items.*.price' => 'required|numeric|min:0',
                'quote_data.items.*.discount' => 'nullable|numeric|min:0',
                'quote_data.items.*.category_id' => 'nullable|exists:categories,id',
                'quote_data.items.*.product_id' => 'nullable|exists:products,id',
                'quote_data.items.*.tax_data' => 'nullable|array',
                'quote_data.discount_amount' => 'nullable|numeric|min:0',
            ]);

            $quoteData = $validated['quote_data'];
            $customer = Client::findOrFail($quoteData['client_id']);

            // Verify customer belongs to current company
            if ($customer->company_id !== auth()->user()->company_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Unauthorized client access',
                ], 403);
            }

            $customerAddress = [
                'state' => $customer->state,
                'city' => $customer->city,
                'zip' => $customer->zip_code,
                'country' => $customer->country ?? 'US',
            ];

            // Prepare items for calculation
            $items = [];
            foreach ($quoteData['items'] as $index => $item) {
                $subtotal = ($item['quantity'] * $item['price']) - ($item['discount'] ?? 0);

                $items[] = [
                    'base_price' => $subtotal,
                    'quantity' => 1, // Already calculated subtotal
                    'name' => $item['name'],
                    'category_id' => $item['category_id'] ?? null,
                    'product_id' => $item['product_id'] ?? null,
                    'tax_data' => $item['tax_data'] ?? [],
                    'customer_address' => $customerAddress,
                    'customer_id' => $customer->id,
                    'line_data' => [
                        'original_quantity' => $item['quantity'],
                        'original_price' => $item['price'],
                        'discount' => $item['discount'] ?? 0,
                        'subtotal' => $subtotal,
                    ],
                ];
            }

            // Calculate taxes for all items
            $results = $this->taxEngine->calculateBulkTaxes($items, 'preview');

            // Calculate quote totals
            $itemsSubtotal = 0;
            $totalTax = 0;

            foreach ($results['bulk_results'] as $result) {
                $itemsSubtotal += $result['base_amount'] ?? 0;
                $totalTax += $result['total_tax_amount'] ?? 0;
            }

            $discountAmount = $quoteData['discount_amount'] ?? 0;
            $finalTotal = $itemsSubtotal - $discountAmount + $totalTax;

            return response()->json([
                'success' => true,
                'data' => [
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'address' => $customerAddress,
                    ],
                    'items' => $results['bulk_results'],
                    'totals' => [
                        'items_subtotal' => $itemsSubtotal,
                        'discount_amount' => $discountAmount,
                        'taxable_amount' => $itemsSubtotal - $discountAmount,
                        'total_tax' => $totalTax,
                        'final_total' => $finalTotal,
                        'effective_tax_rate' => $itemsSubtotal > 0 ? round(($totalTax / $itemsSubtotal) * 100, 4) : 0,
                    ],
                    'performance' => [
                        'calculation_time_ms' => $results['calculation_time_ms'],
                        'items_processed' => $results['total_items'],
                        'engine_breakdown' => $results['engine_breakdown'],
                    ],
                ],
                'preview_id' => uniqid('quote_preview_'),
                'calculated_at' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Quote tax preview error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Quote tax preview failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while previewing quote taxes',
            ], 500);
        }
    }

    /**
     * Clear tax calculation caches
     */
    public function clearCaches(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'nullable|exists:categories,id',
            ]);

            $this->taxEngine->clearTaxCaches($validated['category_id'] ?? null);

            return response()->json([
                'success' => true,
                'message' => 'Tax caches cleared successfully',
                'cleared_for' => $validated['category_id'] ? "category {$validated['category_id']}" : 'entire company',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Tax cache clear error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear caches',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Warm up tax calculation caches
     */
    public function warmCaches(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_ids' => 'nullable|array',
                'category_ids.*' => 'exists:categories,id',
            ]);

            $this->taxEngine->warmUpCaches($validated['category_ids'] ?? []);

            return response()->json([
                'success' => true,
                'message' => 'Tax caches warmed up successfully',
                'categories_cached' => count($validated['category_ids'] ?? []) ?: 'auto-detected',
                'timestamp' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            Log::error('Tax cache warm-up error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to warm up caches',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax calculation performance statistics
     */
    public function getStatistics(Request $request): JsonResponse
    {
        try {
            $stats = $this->taxEngine->getCacheStatistics();

            // Add additional performance metrics
            $stats['current_time'] = now()->toISOString();
            $stats['company_id'] = auth()->user()->company_id;

            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Tax statistics error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve statistics',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax profile and required fields for a category
     */
    public function getTaxProfile(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'nullable|exists:categories,id',
                'category_type' => 'nullable|string',
                'product_id' => 'nullable|exists:products,id',
            ]);

            // Get tax profile
            $profile = $this->profileService->getProfile(
                $validated['category_id'] ?? null,
                $validated['product_id'] ?? null,
                $validated['category_type'] ?? null
            );

            if (! $profile) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tax profile found',
                ], 404);
            }

            // Get profile summary
            $summary = $this->profileService->getProfileSummary($profile);

            return response()->json([
                'success' => true,
                'data' => $summary,
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Get tax profile error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get tax profile',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Get required tax fields for a category
     */
    public function getRequiredFields(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'nullable|exists:categories,id',
                'category_type' => 'nullable|string',
                'product_id' => 'nullable|exists:products,id',
            ]);

            // Get required fields
            $fields = $this->profileService->getRequiredFields(
                $validated['category_id'] ?? null,
                $validated['product_id'] ?? null,
                $validated['category_type'] ?? null
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'required_fields' => $fields,
                    'field_count' => count($fields),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get required fields error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get required fields',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Validate tax data against profile requirements
     */
    public function validateTaxData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_id' => 'nullable|exists:categories,id',
                'category_type' => 'nullable|string',
                'product_id' => 'nullable|exists:products,id',
                'tax_data' => 'required|array',
            ]);

            // Get tax profile
            $profile = $this->profileService->getProfile(
                $validated['category_id'] ?? null,
                $validated['product_id'] ?? null,
                $validated['category_type'] ?? null
            );

            if (! $profile) {
                return response()->json([
                    'success' => false,
                    'error' => 'No tax profile found',
                ], 404);
            }

            // Validate tax data
            $errors = $this->profileService->validateTaxData($validated['tax_data'], $profile);

            if (empty($errors)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Tax data is valid',
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Tax data validation failed',
                    'details' => $errors,
                ], 422);
            }

        } catch (\Exception $e) {
            Log::error('Validate tax data error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
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
            return response()->json([
                'success' => false,
                'error' => 'Customer not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => [
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
                ],
            ],
        ]);
    }

    /**
     * Get all available tax profiles
     */
    public function getAvailableProfiles(): JsonResponse
    {
        try {
            // Ensure default profiles exist
            $this->profileService->ensureDefaultProfiles();

            // Get all profiles
            $profiles = $this->profileService->getAvailableProfiles();

            // Format profiles for response
            $formattedProfiles = $profiles->map(function ($profile) {
                return $this->profileService->getProfileSummary($profile);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'profiles' => $formattedProfiles,
                    'count' => $profiles->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get available profiles error', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get tax profiles',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }

    /**
     * Calculate tax for a single invoice line item
     */
    public function calculateLineItemTax(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                // Line item details
                'base_price' => 'required|numeric|min:0',
                'quantity' => 'nullable|integer|min:1',
                'product_id' => 'nullable|exists:products,id',

                // Customer details
                'customer_id' => 'required|exists:clients,id',

                // Dynamic tax data
                'tax_data' => 'nullable|array',
            ]);

            // Get customer and verify company access
            $customer = Client::find($validated['customer_id']);
            if (! $customer || $customer->company_id !== auth()->user()->company_id) {
                return response()->json([
                    'success' => false,
                    'error' => 'Customer not found or access denied',
                ], 404);
            }

            // Get product if specified
            $product = null;
            if (isset($validated['product_id'])) {
                $product = Product::find($validated['product_id']);
                if (! $product || $product->company_id !== auth()->user()->company_id) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Product not found or access denied',
                    ], 404);
                }
            }

            // Determine category information from product
            $categoryId = $product->category_id ?? null;
            $categoryType = $product ? $product->getTaxEngineType() : 'general';

            // Build customer address
            $customerAddress = [
                'state' => $customer->state,
                'city' => $customer->city,
                'zip' => $customer->zip_code,
                'country' => $customer->country ?? 'US',
            ];

            // Calculate taxes using the router
            $calculation = $this->taxEngine->calculateTaxes([
                'base_price' => $validated['base_price'],
                'quantity' => $validated['quantity'] ?? 1,
                'category_id' => $categoryId,
                'category_type' => $categoryType,
                'tax_data' => $validated['tax_data'] ?? [],
                'customer_address' => $customerAddress,
                'customer_id' => $customer->id,
            ]);

            // Format response for line item usage
            return response()->json([
                'success' => true,
                'data' => [
                    'line_item_id' => $request->input('line_item_id'),
                    'product_id' => $validated['product_id'],
                    'customer_id' => $customer->id,
                    'base_price' => $validated['base_price'],
                    'quantity' => $validated['quantity'] ?? 1,
                    'subtotal' => $calculation['base_amount'],
                    'tax_amount' => $calculation['total_tax_amount'],
                    'tax_rate' => $calculation['effective_tax_rate'] ?? 0,
                    'tax_breakdown' => $calculation['tax_breakdown'] ?? [],
                    'total' => $calculation['final_amount'],
                    'engine_used' => $calculation['engine_used'] ?? 'general',
                    'tax_profile' => $calculation['tax_profile'] ?? null,
                    'jurisdictions' => $calculation['jurisdictions'] ?? [],
                    'customer_address' => $customerAddress,
                ],
                'calculation_id' => uniqid('line_calc_'),
                'calculated_at' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Line item tax calculation error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Tax calculation failed',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred while calculating tax',
            ], 500);
        }
    }

    /**
     * Get applicable tax types for a category
     */
    public function getApplicableTaxTypes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_type' => 'required|string',
            ]);

            $taxTypes = $this->taxEngine->getApplicableTaxTypes($validated['category_type']);

            return response()->json([
                'success' => true,
                'data' => [
                    'tax_types' => $taxTypes,
                    'count' => count($taxTypes),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get applicable tax types error', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get tax types',
                'message' => config('app.debug') ? $e->getMessage() : 'An error occurred',
            ], 500);
        }
    }
}
