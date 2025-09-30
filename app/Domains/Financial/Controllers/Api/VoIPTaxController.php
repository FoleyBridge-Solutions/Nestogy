<?php

namespace App\Domains\Financial\Controllers\Api;

use App\Domains\Financial\Services\TaxRateManagementService;
use App\Domains\Financial\Services\VoIPTaxComplianceService;
use App\Domains\Financial\Services\VoIPTaxService;
use App\Http\Controllers\Controller;
use App\Models\TaxCategory;
use App\Models\TaxExemption;
use App\Models\TaxJurisdiction;
use App\Models\VoIPTaxRate;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * VoIP Tax API Controller
 *
 * Provides REST API endpoints for VoIP tax calculations, management, and compliance.
 */
class VoIPTaxController extends Controller
{
    /**
     * Calculate VoIP taxes for a service amount.
     */
    public function calculateTaxes(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0',
                'service_type' => 'required|string|in:local,long_distance,international,voip_fixed,voip_nomadic,data,equipment',
                'service_address' => 'nullable|array',
                'service_address.address' => 'nullable|string',
                'service_address.city' => 'nullable|string',
                'service_address.state' => 'nullable|string',
                'service_address.zip_code' => 'nullable|string',
                'service_address.country' => 'nullable|string',
                'client_id' => 'nullable|integer|exists:clients,id',
                'calculation_date' => 'nullable|date',
                'line_count' => 'nullable|integer|min:1',
                'minutes' => 'nullable|integer|min:0',
            ]);

            $companyId = auth()->user()->company_id;
            $taxService = new VoIPTaxService;
            $taxService->setCompanyId($companyId);

            $calculation = $taxService->calculateTaxes($validated);

            return response()->json([
                'success' => true,
                'data' => $calculation,
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
            return response()->json([
                'success' => false,
                'error' => 'Tax calculation failed',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax rates for a specific jurisdiction and category.
     */
    public function getTaxRates(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'jurisdiction_id' => 'nullable|integer|exists:tax_jurisdictions,id',
                'category_id' => 'nullable|integer|exists:tax_categories,id',
                'service_type' => 'nullable|string',
                'tax_type' => 'nullable|string|in:federal,state,local,municipal,county,special_district',
                'is_active' => 'nullable|boolean',
                'effective_date' => 'nullable|date',
            ]);

            $companyId = auth()->user()->company_id;
            $query = VoIPTaxRate::where('company_id', $companyId)
                ->with(['jurisdiction', 'category']);

            if (isset($validated['jurisdiction_id'])) {
                $query->where('tax_jurisdiction_id', $validated['jurisdiction_id']);
            }

            if (isset($validated['category_id'])) {
                $query->where('tax_category_id', $validated['category_id']);
            }

            if (isset($validated['tax_type'])) {
                $query->where('tax_type', $validated['tax_type']);
            }

            if (isset($validated['is_active'])) {
                $query->where('is_active', $validated['is_active']);
            } else {
                $query->where('is_active', true);
            }

            if (isset($validated['effective_date'])) {
                $effectiveDate = Carbon::parse($validated['effective_date']);
                $query->where('effective_date', '<=', $effectiveDate)
                    ->where(function ($q) use ($effectiveDate) {
                        $q->whereNull('expiry_date')
                            ->orWhere('expiry_date', '>', $effectiveDate);
                    });
            }

            if (isset($validated['service_type'])) {
                $query->forServiceType($validated['service_type']);
            }

            $taxRates = $query->orderBy('priority')->get();

            return response()->json([
                'success' => true,
                'data' => $taxRates->map(function ($rate) {
                    return [
                        'id' => $rate->id,
                        'jurisdiction' => [
                            'id' => $rate->jurisdiction->id,
                            'name' => $rate->jurisdiction->name,
                            'type' => $rate->jurisdiction->jurisdiction_type,
                        ],
                        'category' => [
                            'id' => $rate->category->id,
                            'name' => $rate->category->name,
                            'code' => $rate->category->code,
                        ],
                        'tax_name' => $rate->tax_name,
                        'tax_type' => $rate->tax_type,
                        'rate_type' => $rate->rate_type,
                        'percentage_rate' => $rate->percentage_rate,
                        'fixed_amount' => $rate->fixed_amount,
                        'minimum_threshold' => $rate->minimum_threshold,
                        'maximum_amount' => $rate->maximum_amount,
                        'calculation_method' => $rate->calculation_method,
                        'authority_name' => $rate->authority_name,
                        'service_types' => $rate->service_types,
                        'effective_date' => $rate->effective_date->toISOString(),
                        'expiry_date' => $rate->expiry_date?->toISOString(),
                        'is_active' => $rate->is_active,
                        'priority' => $rate->priority,
                        'formatted_rate' => $rate->getFormattedRate(),
                    ];
                }),
                'count' => $taxRates->count(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve tax rates',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available tax jurisdictions.
     */
    public function getJurisdictions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'jurisdiction_type' => 'nullable|string|in:federal,state,county,city,municipality,special_district,zip_code',
                'state_code' => 'nullable|string|size:2',
                'is_active' => 'nullable|boolean',
            ]);

            $companyId = auth()->user()->company_id;
            $query = TaxJurisdiction::where('company_id', $companyId);

            if (isset($validated['jurisdiction_type'])) {
                $query->where('jurisdiction_type', $validated['jurisdiction_type']);
            }

            if (isset($validated['state_code'])) {
                $query->where('state_code', $validated['state_code']);
            }

            if (isset($validated['is_active'])) {
                $query->where('is_active', $validated['is_active']);
            } else {
                $query->where('is_active', true);
            }

            $jurisdictions = $query->orderBy('priority')->get();

            return response()->json([
                'success' => true,
                'data' => $jurisdictions->map(function ($jurisdiction) {
                    return [
                        'id' => $jurisdiction->id,
                        'name' => $jurisdiction->name,
                        'code' => $jurisdiction->code,
                        'jurisdiction_type' => $jurisdiction->jurisdiction_type,
                        'jurisdiction_type_label' => $jurisdiction->getJurisdictionTypeLabel(),
                        'state_code' => $jurisdiction->state_code,
                        'authority_name' => $jurisdiction->authority_name,
                        'website' => $jurisdiction->website,
                        'filing_requirements' => $jurisdiction->filing_requirements,
                        'is_active' => $jurisdiction->is_active,
                        'priority' => $jurisdiction->priority,
                    ];
                }),
                'count' => $jurisdictions->count(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve jurisdictions',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax categories.
     */
    public function getCategories(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'category_type' => 'nullable|string|in:telecommunications,internet,data_services,equipment,installation,maintenance,hosting,software',
                'is_taxable' => 'nullable|boolean',
                'is_active' => 'nullable|boolean',
            ]);

            $companyId = auth()->user()->company_id;
            $query = TaxCategory::where('company_id', $companyId);

            if (isset($validated['category_type'])) {
                $query->where('category_type', $validated['category_type']);
            }

            if (isset($validated['is_taxable'])) {
                $query->where('is_taxable', $validated['is_taxable']);
            }

            if (isset($validated['is_active'])) {
                $query->where('is_active', $validated['is_active']);
            } else {
                $query->where('is_active', true);
            }

            $categories = $query->orderBy('priority')->get();

            return response()->json([
                'success' => true,
                'data' => $categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'code' => $category->code,
                        'category_type' => $category->category_type,
                        'category_type_label' => $category->getCategoryTypeLabel(),
                        'description' => $category->description,
                        'service_types' => $category->service_types,
                        'is_taxable' => $category->is_taxable,
                        'is_interstate' => $category->is_interstate,
                        'is_international' => $category->is_international,
                        'requires_jurisdiction_detection' => $category->requires_jurisdiction_detection,
                        'default_tax_treatment' => $category->default_tax_treatment,
                        'is_active' => $category->is_active,
                        'priority' => $category->priority,
                    ];
                }),
                'count' => $categories->count(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve categories',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get tax exemptions for a client.
     */
    public function getClientExemptions(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'client_id' => 'required|integer|exists:clients,id',
                'status' => 'nullable|string|in:active,expired,suspended,revoked,pending',
                'exemption_type' => 'nullable|string',
            ]);

            $companyId = auth()->user()->company_id;
            $query = TaxExemption::where('company_id', $companyId)
                ->where('client_id', $validated['client_id'])
                ->with(['client', 'jurisdiction', 'category']);

            if (isset($validated['status'])) {
                $query->where('status', $validated['status']);
            }

            if (isset($validated['exemption_type'])) {
                $query->where('exemption_type', $validated['exemption_type']);
            }

            $exemptions = $query->orderBy('priority')->get();

            return response()->json([
                'success' => true,
                'data' => $exemptions->map(function ($exemption) {
                    return [
                        'id' => $exemption->id,
                        'exemption_name' => $exemption->exemption_name,
                        'exemption_type' => $exemption->exemption_type,
                        'exemption_type_label' => $exemption->getExemptionTypeLabel(),
                        'certificate_number' => $exemption->certificate_number,
                        'issuing_authority' => $exemption->issuing_authority,
                        'issue_date' => $exemption->issue_date?->toDateString(),
                        'expiry_date' => $exemption->expiry_date?->toDateString(),
                        'is_blanket_exemption' => $exemption->is_blanket_exemption,
                        'applicable_tax_types' => $exemption->applicable_tax_types,
                        'applicable_services' => $exemption->applicable_services,
                        'exemption_percentage' => $exemption->exemption_percentage,
                        'maximum_exemption_amount' => $exemption->maximum_exemption_amount,
                        'status' => $exemption->status,
                        'status_label' => $exemption->getStatusLabel(),
                        'verification_status' => $exemption->verification_status,
                        'verification_status_label' => $exemption->getVerificationStatusLabel(),
                        'is_valid' => $exemption->isValid(),
                        'is_expired' => $exemption->isExpired(),
                        'is_expiring_soon' => $exemption->isExpiringSoon(),
                        'auto_apply' => $exemption->auto_apply,
                        'priority' => $exemption->priority,
                    ];
                }),
                'count' => $exemptions->count(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to retrieve exemptions',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate compliance report.
     */
    public function generateComplianceReport(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'jurisdictions' => 'nullable|array',
                'jurisdictions.*' => 'integer|exists:tax_jurisdictions,id',
                'format' => 'nullable|string|in:json,summary',
            ]);

            $companyId = auth()->user()->company_id;
            $complianceService = new VoIPTaxComplianceService($companyId);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $jurisdictions = $validated['jurisdictions'] ?? [];

            if ($validated['format'] === 'summary') {
                $report = $complianceService->generateComplianceReport($startDate, $endDate, $jurisdictions);

                // Return summarized version
                $summary = [
                    'period' => $report['reporting_period'],
                    'jurisdictions_count' => count($report['jurisdictions']),
                    'total_base_amount' => array_sum(array_column(array_column($report['jurisdictions'], 'totals'), 'base_amount')),
                    'total_tax_amount' => array_sum(array_column(array_column($report['jurisdictions'], 'totals'), 'tax_amount')),
                    'total_exemptions' => array_sum(array_column(array_column($report['jurisdictions'], 'totals'), 'exemptions_amount')),
                    'net_tax_amount' => array_sum(array_column(array_column($report['jurisdictions'], 'totals'), 'net_tax_amount')),
                ];

                return response()->json([
                    'success' => true,
                    'data' => $summary,
                    'generated_at' => $report['generated_at'],
                ]);
            } else {
                $report = $complianceService->generateComplianceReport($startDate, $endDate, $jurisdictions);

                return response()->json([
                    'success' => true,
                    'data' => $report,
                ]);
            }

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to generate compliance report',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check compliance status.
     */
    public function checkComplianceStatus(): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $complianceService = new VoIPTaxComplianceService($companyId);

            $status = $complianceService->checkComplianceStatus();

            return response()->json([
                'success' => true,
                'data' => $status,
                'checked_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to check compliance status',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export compliance data.
     */
    public function exportComplianceData(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'format' => 'nullable|string|in:json,csv,xml',
            ]);

            $companyId = auth()->user()->company_id;
            $complianceService = new VoIPTaxComplianceService($companyId);

            $startDate = Carbon::parse($validated['start_date']);
            $endDate = Carbon::parse($validated['end_date']);
            $format = $validated['format'] ?? 'json';

            $filepath = $complianceService->exportComplianceData($startDate, $endDate, $format);

            return response()->json([
                'success' => true,
                'data' => [
                    'filepath' => $filepath,
                    'format' => $format,
                    'period' => [
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                    ],
                    'download_url' => route('api.voip-tax.download-export', ['filepath' => base64_encode($filepath)]),
                ],
                'exported_at' => now()->toISOString(),
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to export compliance data',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create or update tax rate.
     */
    public function createOrUpdateTaxRate(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate(VoIPTaxRate::getValidationRules());

            $companyId = auth()->user()->company_id;
            $managementService = new TaxRateManagementService($companyId);

            $taxRate = $managementService->createOrUpdateTaxRate(
                $validated,
                auth()->id(),
                $request->input('change_reason', 'API update')
            );

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $taxRate->id,
                    'tax_name' => $taxRate->tax_name,
                    'tax_type' => $taxRate->tax_type,
                    'rate_type' => $taxRate->rate_type,
                    'formatted_rate' => $taxRate->getFormattedRate(),
                    'is_active' => $taxRate->is_active,
                    'effective_date' => $taxRate->effective_date->toISOString(),
                    'created_at' => $taxRate->created_at->toISOString(),
                    'updated_at' => $taxRate->updated_at->toISOString(),
                ],
                'message' => isset($validated['id']) ? 'Tax rate updated successfully' : 'Tax rate created successfully',
            ], isset($validated['id']) ? 200 : 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to create/update tax rate',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initialize default tax rates for the company.
     */
    public function initializeDefaultRates(): JsonResponse
    {
        try {
            $companyId = auth()->user()->company_id;
            $managementService = new TaxRateManagementService($companyId);

            $results = $managementService->initializeDefaultRates();

            return response()->json([
                'success' => true,
                'data' => $results,
                'message' => 'Default tax rates initialized successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to initialize default rates',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get service types available for VoIP taxation.
     */
    public function getServiceTypes(): JsonResponse
    {
        $serviceTypes = VoIPTaxRate::getAvailableServiceTypes();

        return response()->json([
            'success' => true,
            'data' => collect($serviceTypes)->map(function ($label, $value) {
                return [
                    'value' => $value,
                    'label' => $label,
                ];
            })->values(),
        ]);
    }

    /**
     * Clear tax calculation cache.
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'pattern' => 'nullable|string',
            ]);

            $companyId = auth()->user()->company_id;
            $taxService = new VoIPTaxService;
            $taxService->setCompanyId($companyId);

            $pattern = $validated['pattern'] ?? null;
            $taxService->clearCache($pattern);

            return response()->json([
                'success' => true,
                'message' => 'Tax calculation cache cleared successfully',
                'pattern' => $pattern,
                'cleared_at' => now()->toISOString(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
