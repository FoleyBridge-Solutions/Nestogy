<?php

namespace App\Services\TaxEngine;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * TaxCloud V3 API Client
 * 
 * Updated client for TaxCloud V3 API which uses different authentication
 * and endpoints compared to V1. Uses Connection ID and X-API-KEY header.
 * 
 * API Documentation: https://docs.taxcloud.com/
 */
class TaxCloudV3ApiClient
{
    protected string $baseUrl = 'https://api.v3.taxcloud.com/tax';
    protected ?string $connectionId;
    protected ?string $apiKey;
    protected int $companyId;
    protected int $timeout = 30;

    public function __construct(int $companyId, array $config = [])
    {
        $this->companyId = $companyId;
        $this->connectionId = $config['connection_id'] ?? env('TAXCLOUD_CONNECTION_ID');
        $this->apiKey = $config['api_key'] ?? env('TAXCLOUD_V3_API_KEY_ENCODED');
        $this->timeout = $config['timeout'] ?? 30;
    }

    /**
     * Check if credentials are configured
     */
    public function hasValidCredentials(): bool
    {
        return !empty($this->connectionId) && !empty($this->apiKey);
    }

    /**
     * Get configuration status
     */
    public function getConfigurationStatus(): array
    {
        return [
            'configured' => $this->hasValidCredentials(),
            'connection_id' => $this->connectionId ? 'Set' : 'Missing',
            'api_key' => $this->apiKey ? 'Set' : 'Missing',
            'api_version' => 'v3',
        ];
    }

    /**
     * Calculate tax for equipment/products using TaxCloud V3 carts endpoint
     */
    public function calculateEquipmentTax(
        float $amount,
        array $destination,
        array $origin = null,
        string $customerId = null,
        array $lineItems = []
    ): array {
        if (!$this->hasValidCredentials()) {
            return $this->getCredentialsErrorResponse();
        }

        try {
            // Use default origin if not provided
            $origin = $origin ?? $this->getDefaultOrigin();
            
            // Prepare line items
            if (empty($lineItems)) {
                $lineItems = [
                    [
                        'index' => 0,
                        'itemId' => 'EQUIP-' . time(),
                        'tic' => 0, // General tangible personal property
                        'price' => $amount,
                        'quantity' => 1
                    ]
                ];
            }

            // Create cart payload
            $payload = [
                'items' => [
                    [
                        'currency' => [
                            'currencyCode' => 'USD'
                        ],
                        'customerId' => $customerId ?? 'customer-' . $this->companyId,
                        'destination' => $this->formatAddress($destination),
                        'origin' => $this->formatAddress($origin),
                        'lineItems' => $lineItems
                    ]
                ]
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post("{$this->baseUrl}/connections/{$this->connectionId}/carts", $payload);

            if ($response->successful()) {
                $data = $response->json();
                return $this->formatTaxResponse($data);
            } else {
                $error = $response->json();
                throw new Exception("TaxCloud V3 API error: " . ($error['message'] ?? $response->body()));
            }

        } catch (Exception $e) {
            Log::error('TaxCloud V3 tax calculation failed', [
                'error' => $e->getMessage(),
                'amount' => $amount,
                'destination' => $destination
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'subtotal' => $amount,
                'tax_amount' => 0,
                'total' => $amount,
                'tax_rate' => 0,
                'source' => 'taxcloud_v3_error'
            ];
        }
    }

    /**
     * Format address for TaxCloud V3 API
     */
    protected function formatAddress(array $address): array
    {
        return [
            'line1' => $address['address_line_1'] ?? $address['line1'] ?? $address['street'] ?? '',
            'line2' => $address['address_line_2'] ?? $address['line2'] ?? '',
            'city' => $address['city'] ?? '',
            'state' => $address['state'] ?? $address['state_code'] ?? '',
            'zip' => $address['postal_code'] ?? $address['zip'] ?? $address['zip_code'] ?? '',
            'countryCode' => $address['country'] ?? $address['country_code'] ?? 'US'
        ];
    }

    /**
     * Get default origin address (your business location)
     */
    protected function getDefaultOrigin(): array
    {
        // TODO: Make this configurable per company
        return [
            'line1' => '123 Business Street',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'zip' => '90210',
            'countryCode' => 'US'
        ];
    }

    /**
     * Format TaxCloud V3 response to standard format
     */
    protected function formatTaxResponse(array $data): array
    {
        try {
            if (!isset($data['items'][0])) {
                throw new Exception('Invalid response format from TaxCloud V3');
            }

            $item = $data['items'][0];
            $lineItems = $item['lineItems'] ?? [];
            
            $subtotal = 0;
            $totalTax = 0;
            $jurisdictions = [];
            
            foreach ($lineItems as $lineItem) {
                $subtotal += ($lineItem['price'] ?? 0) * ($lineItem['quantity'] ?? 1);
                $totalTax += $lineItem['tax']['amount'] ?? 0;
                
                // Extract jurisdiction information if available
                if (isset($lineItem['tax']['jurisdictions'])) {
                    foreach ($lineItem['tax']['jurisdictions'] as $jurisdiction) {
                        $jurisdictions[] = [
                            'name' => $jurisdiction['name'] ?? 'Unknown',
                            'type' => $jurisdiction['type'] ?? 'unknown',
                            'tax_amount' => $jurisdiction['tax'] ?? 0,
                            'tax_rate' => $jurisdiction['rate'] ?? 0,
                        ];
                    }
                }
            }

            $effectiveRate = $subtotal > 0 ? ($totalTax / $subtotal) * 100 : 0;

            return [
                'success' => true,
                'subtotal' => $subtotal,
                'tax_amount' => $totalTax,
                'total' => $subtotal + $totalTax,
                'tax_rate' => $effectiveRate,
                'cart_id' => $item['cartId'] ?? null,
                'customer_id' => $item['customerId'] ?? null,
                'jurisdictions' => $jurisdictions,
                'line_items' => $lineItems,
                'source' => 'taxcloud_v3',
                'response_data' => $data
            ];

        } catch (Exception $e) {
            Log::error('Failed to format TaxCloud V3 response', [
                'error' => $e->getMessage(),
                'response' => $data
            ]);

            return [
                'success' => false,
                'error' => 'Failed to parse TaxCloud response: ' . $e->getMessage(),
                'subtotal' => 0,
                'tax_amount' => 0,
                'total' => 0,
                'tax_rate' => 0,
                'source' => 'taxcloud_v3_parse_error'
            ];
        }
    }

    /**
     * Convert cart to order (when customer completes purchase)
     */
    public function convertCartToOrder(string $cartId, string $orderId, bool $completed = true): array
    {
        if (!$this->hasValidCredentials()) {
            return $this->getCredentialsErrorResponse();
        }

        try {
            $payload = [
                'completed' => $completed,
                'cartId' => $cartId,
                'orderId' => $orderId
            ];

            if ($completed) {
                $payload['completedDate'] = now()->toISOString();
            }

            $response = Http::timeout($this->timeout)
                ->withHeaders([
                    'X-API-KEY' => $this->apiKey,
                    'Content-Type' => 'application/json'
                ])
                ->post("{$this->baseUrl}/connections/{$this->connectionId}/carts/orders", $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'order_data' => $response->json()
                ];
            } else {
                $error = $response->json();
                throw new Exception("Failed to convert cart to order: " . ($error['message'] ?? $response->body()));
            }

        } catch (Exception $e) {
            Log::error('TaxCloud V3 cart to order conversion failed', [
                'error' => $e->getMessage(),
                'cart_id' => $cartId,
                'order_id' => $orderId
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Test the connection to TaxCloud V3
     */
    public function testConnection(): array
    {
        if (!$this->hasValidCredentials()) {
            return $this->getCredentialsErrorResponse();
        }

        try {
            // Test with a simple cart creation
            $testResult = $this->calculateEquipmentTax(
                100.00,
                [
                    'line1' => '162 E Ave',
                    'city' => 'Norwalk',
                    'state' => 'CT',
                    'zip' => '06851'
                ],
                null,
                'test-customer'
            );

            return [
                'success' => $testResult['success'],
                'connection_status' => $testResult['success'] ? 'Connected' : 'Failed',
                'test_result' => $testResult,
                'api_version' => 'v3'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'connection_status' => 'Failed',
                'error' => $e->getMessage(),
                'api_version' => 'v3'
            ];
        }
    }

    /**
     * Get error response for missing credentials
     */
    protected function getCredentialsErrorResponse(): array
    {
        return [
            'success' => false,
            'error' => 'TaxCloud V3 credentials not configured. Please set TAXCLOUD_CONNECTION_ID and TAXCLOUD_V3_API_KEY in your .env file.',
            'subtotal' => 0,
            'tax_amount' => 0,
            'total' => 0,
            'tax_rate' => 0,
            'source' => 'configuration_error'
        ];
    }
}