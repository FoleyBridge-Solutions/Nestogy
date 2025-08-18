<?php

namespace App\Services\TaxEngine;

use App\Models\TaxApiQueryCache;
use Exception;

/**
 * TaxCloud API Client
 * 
 * Free API for US sales tax calculations. TaxCloud provides
 * accurate sales tax rates for all US jurisdictions.
 * 
 * API Documentation: https://taxcloud.com/api/
 * Free tier: 10,000 API calls per month
 */
class TaxCloudApiClient extends BaseApiClient
{
    protected string $baseUrl = 'https://api.taxcloud.com/1.0/TaxCloud';
    protected ?string $apiLoginId;
    protected ?string $apiKey;
    protected ?string $customerId;

    public function __construct(int $companyId, array $config = [])
    {
        parent::__construct($companyId, TaxApiQueryCache::PROVIDER_TAXCLOUD, $config);
        
        $this->apiLoginId = $config['api_login_id'] ?? env('TAXCLOUD_API_LOGIN_ID');
        $this->apiKey = $config['api_key'] ?? env('TAXCLOUD_API_KEY');
        $this->customerId = $config['customer_id'] ?? env('TAXCLOUD_CUSTOMER_ID');
    }

    /**
     * Get rate limits for TaxCloud API
     * Free tier allows 10,000 calls per month
     */
    protected function getRateLimits(): array
    {
        return [
            TaxApiQueryCache::TYPE_TAX_RATES => [
                'max_requests' => 50,
                'window' => 60, // per minute
            ],
            'lookup' => [
                'max_requests' => 50,
                'window' => 60,
            ],
            'verify_address' => [
                'max_requests' => 30,
                'window' => 60,
            ],
        ];
    }

    /**
     * Lookup tax rates for an address
     * 
     * @param array $address Address components
     * @param array $cartItems Items to calculate tax for
     * @return array Tax calculation results
     */
    public function lookupTax(array $address, array $cartItems): array
    {
        if (!$this->hasValidCredentials()) {
            return $this->getCredentialsErrorResponse();
        }

        $parameters = [
            'address' => $address,
            'cart_items' => $cartItems,
            'customer_id' => $this->customerId,
        ];
        
        return $this->makeRequest(
            TaxApiQueryCache::TYPE_TAX_RATES,
            $parameters,
            function () use ($address, $cartItems) {
                $requestData = [
                    'apiLoginID' => $this->apiLoginId,
                    'apiKey' => $this->apiKey,
                    'customerID' => $this->customerId,
                    'cartID' => uniqid('cart_', true),
                    'cartItems' => $this->formatCartItems($cartItems),
                    'origin' => $this->getOriginAddress(),
                    'destination' => $this->formatDestinationAddress($address),
                    'deliveredBySeller' => false,
                ];

                $response = $this->createHttpClient()
                    ->post("{$this->baseUrl}/Lookup", $requestData);

                if (!$response->successful()) {
                    throw new Exception("TaxCloud lookup failed: " . $response->body());
                }

                $data = $response->json();
                
                if (isset($data['ResponseType']) && $data['ResponseType'] === 'Error') {
                    throw new Exception("TaxCloud API error: " . ($data['Messages'][0] ?? 'Unknown error'));
                }

                return $this->formatLookupResponse($data, $cartItems, $address);
            },
            1 // Cache tax lookups for 1 day (rates can change)
        );
    }

    /**
     * Verify an address using TaxCloud
     * 
     * @param array $address Address to verify
     * @return array Address verification result
     */
    public function verifyAddress(array $address): array
    {
        if (!$this->hasValidCredentials()) {
            return $this->getCredentialsErrorResponse();
        }

        $parameters = [
            'address' => $address,
        ];
        
        return $this->makeRequest(
            'verify_address',
            $parameters,
            function () use ($address) {
                $requestData = [
                    'apiLoginID' => $this->apiLoginId,
                    'apiKey' => $this->apiKey,
                    'uspsUserID' => '', // Optional USPS integration
                    'Address1' => $address['address1'] ?? '',
                    'Address2' => $address['address2'] ?? '',
                    'City' => $address['city'] ?? '',
                    'State' => $address['state'] ?? '',
                    'Zip5' => substr($address['zip'] ?? '', 0, 5),
                    'Zip4' => substr($address['zip'] ?? '', 5, 4),
                ];

                $response = $this->createHttpClient()
                    ->post("{$this->baseUrl}/VerifyAddress", $requestData);

                if (!$response->successful()) {
                    throw new Exception("TaxCloud address verification failed: " . $response->body());
                }

                $data = $response->json();
                
                if (isset($data['ResponseType']) && $data['ResponseType'] === 'Error') {
                    throw new Exception("TaxCloud verification error: " . ($data['Messages'][0] ?? 'Unknown error'));
                }

                return [
                    'verified' => isset($data['Address1']),
                    'original_address' => $address,
                    'verified_address' => [
                        'address1' => $data['Address1'] ?? null,
                        'address2' => $data['Address2'] ?? null,
                        'city' => $data['City'] ?? null,
                        'state' => $data['State'] ?? null,
                        'zip5' => $data['Zip5'] ?? null,
                        'zip4' => $data['Zip4'] ?? null,
                        'zip' => ($data['Zip5'] ?? '') . ($data['Zip4'] ?? ''),
                    ],
                    'error_number' => $data['ErrNumber'] ?? null,
                    'source' => 'taxcloud',
                ];
            },
            30 // Cache address verifications for 30 days
        );
    }

    /**
     * Get tax information for a specific jurisdiction
     * 
     * @param string $state State abbreviation
     * @param string $zip ZIP code
     * @return array Jurisdiction tax information
     */
    public function getJurisdictionTaxInfo(string $state, string $zip): array
    {
        $parameters = [
            'state' => $state,
            'zip' => $zip,
        ];
        
        return $this->makeRequest(
            TaxApiQueryCache::TYPE_JURISDICTION,
            $parameters,
            function () use ($state, $zip) {
                // Create a simple lookup to get jurisdiction info
                $cartItems = [
                    [
                        'index' => 0,
                        'item_id' => 'test_item',
                        'tic' => '00000', // General merchandise
                        'price' => 100.00,
                        'quantity' => 1,
                        'description' => 'Test item for jurisdiction lookup',
                    ]
                ];

                $address = [
                    'state' => $state,
                    'zip' => $zip,
                    'city' => '',
                    'address1' => '123 Main St',
                ];

                $lookup = $this->lookupTax($address, $cartItems);
                
                if (!$lookup['success']) {
                    throw new Exception("Failed to get jurisdiction info: " . ($lookup['error'] ?? 'Unknown error'));
                }

                return [
                    'state' => $state,
                    'zip' => $zip,
                    'jurisdictions' => $lookup['jurisdictions'] ?? [],
                    'total_rate' => $lookup['total_rate'] ?? 0,
                    'state_rate' => $lookup['state_rate'] ?? 0,
                    'county_rate' => $lookup['county_rate'] ?? 0,
                    'city_rate' => $lookup['city_rate'] ?? 0,
                    'special_rate' => $lookup['special_rate'] ?? 0,
                    'source' => 'taxcloud',
                ];
            },
            7 // Cache jurisdiction info for 7 days
        );
    }

    /**
     * Calculate tax for MSP services
     * 
     * @param float $amount Service amount
     * @param string $serviceType Type of service
     * @param array $customerAddress Customer address
     * @param string $tic Tax Item Code for the service
     * @return array Tax calculation result
     */
    public function calculateServiceTax(float $amount, string $serviceType, array $customerAddress, string $tic = '30070'): array
    {
        // MSP services typically use TIC 30070 (Computer Services)
        $ticMap = [
            'managed_services' => '30070', // Computer Services
            'cloud_services' => '30070',   // Computer Services
            'hosting' => '30070',          // Computer Services
            'voip' => '10115',            // Telephone Services
            'internet' => '30070',         // Computer Services
            'security_services' => '30070', // Computer Services
            'backup_services' => '30070',   // Computer Services
            'monitoring' => '30070',        // Computer Services
            'support' => '30070',          // Computer Services
            'consultation' => '30070',     // Computer Services
            'installation' => '30070',     // Computer Services
            'equipment' => '00000',        // General merchandise
            'software' => '30240',         // Prewritten Software
        ];

        $selectedTic = $ticMap[$serviceType] ?? $tic;

        $cartItems = [
            [
                'index' => 0,
                'item_id' => uniqid('service_'),
                'tic' => $selectedTic,
                'price' => $amount,
                'quantity' => 1,
                'description' => ucwords(str_replace('_', ' ', $serviceType)),
            ]
        ];

        return $this->lookupTax($customerAddress, $cartItems);
    }

    /**
     * Check if valid credentials are provided
     */
    protected function hasValidCredentials(): bool
    {
        return !empty($this->apiLoginId) && !empty($this->apiKey) && !empty($this->customerId);
    }

    /**
     * Get credentials error response
     */
    protected function getCredentialsErrorResponse(): array
    {
        return [
            'success' => false,
            'error' => 'TaxCloud credentials not configured',
            'available' => false,
            'source' => 'taxcloud_config_error',
        ];
    }

    /**
     * Format cart items for TaxCloud API
     */
    protected function formatCartItems(array $cartItems): array
    {
        $formatted = [];
        
        foreach ($cartItems as $index => $item) {
            $formatted[] = [
                'Index' => $item['index'] ?? $index,
                'ItemID' => $item['item_id'] ?? "item_{$index}",
                'TIC' => $item['tic'] ?? '00000',
                'Price' => (float) ($item['price'] ?? 0),
                'Qty' => (float) ($item['quantity'] ?? 1),
            ];
        }
        
        return $formatted;
    }

    /**
     * Get origin address (business address)
     */
    protected function getOriginAddress(): array
    {
        // This would typically come from company settings
        return [
            'Address1' => env('COMPANY_ADDRESS1', '123 Business St'),
            'Address2' => env('COMPANY_ADDRESS2', ''),
            'City' => env('COMPANY_CITY', 'Los Angeles'),
            'State' => env('COMPANY_STATE', 'CA'),
            'Zip5' => env('COMPANY_ZIP5', '90210'),
            'Zip4' => env('COMPANY_ZIP4', ''),
        ];
    }

    /**
     * Format destination address for TaxCloud
     */
    protected function formatDestinationAddress(array $address): array
    {
        return [
            'Address1' => $address['address1'] ?? $address['address_line_1'] ?? $address['street'] ?? '',
            'Address2' => $address['address2'] ?? $address['address_line_2'] ?? '',
            'City' => $address['city'] ?? '',
            'State' => $address['state'] ?? '',
            'Zip5' => substr($address['zip'] ?? $address['postal_code'] ?? '', 0, 5),
            'Zip4' => substr($address['zip'] ?? $address['postal_code'] ?? '', 5, 4),
        ];
    }

    /**
     * Format lookup response from TaxCloud
     */
    protected function formatLookupResponse(array $data, array $cartItems, array $address): array
    {
        $cartItemsResponse = $data['CartItemsResponse'] ?? [];
        $totalTax = 0;
        $itemBreakdown = [];
        $jurisdictions = [];

        foreach ($cartItemsResponse as $index => $itemResponse) {
            $tax = (float) ($itemResponse['TaxAmount'] ?? 0);
            $totalTax += $tax;
            
            $originalItem = $cartItems[$index] ?? [];
            $itemBreakdown[] = [
                'index' => $index,
                'item_id' => $originalItem['item_id'] ?? '',
                'description' => $originalItem['description'] ?? '',
                'price' => $originalItem['price'] ?? 0,
                'quantity' => $originalItem['quantity'] ?? 1,
                'tax_amount' => $tax,
                'taxable' => $tax > 0,
                'tic' => $originalItem['tic'] ?? '00000',
            ];

            // Extract jurisdiction information from first item
            if ($index === 0 && isset($itemResponse['TaxDetails'])) {
                foreach ($itemResponse['TaxDetails'] as $detail) {
                    $jurisdictions[] = [
                        'jurisdiction_type' => $detail['JurisdictionType'] ?? '',
                        'jurisdiction_fips' => $detail['JurisdictionFips'] ?? '',
                        'jurisdiction_name' => $detail['JurisdictionName'] ?? '',
                        'tax_rate' => (float) ($detail['Rate'] ?? 0),
                        'tax_amount' => (float) ($detail['Tax'] ?? 0),
                        'taxable_amount' => (float) ($detail['Taxable'] ?? 0),
                    ];
                }
            }
        }

        // Calculate total rates by jurisdiction type
        $rates = [
            'state_rate' => 0,
            'county_rate' => 0,
            'city_rate' => 0,
            'special_rate' => 0,
        ];

        foreach ($jurisdictions as $jurisdiction) {
            $rate = $jurisdiction['tax_rate'];
            switch (strtolower($jurisdiction['jurisdiction_type'])) {
                case 'state':
                    $rates['state_rate'] += $rate;
                    break;
                case 'county':
                    $rates['county_rate'] += $rate;
                    break;
                case 'city':
                    $rates['city_rate'] += $rate;
                    break;
                default:
                    $rates['special_rate'] += $rate;
                    break;
            }
        }

        $totalRate = array_sum($rates);
        
        return [
            'success' => true,
            'total_tax_amount' => round($totalTax, 2),
            'total_rate' => round($totalRate, 6),
            'state_rate' => round($rates['state_rate'], 6),
            'county_rate' => round($rates['county_rate'], 6),
            'city_rate' => round($rates['city_rate'], 6),
            'special_rate' => round($rates['special_rate'], 6),
            'jurisdictions' => $jurisdictions,
            'item_breakdown' => $itemBreakdown,
            'cart_id' => $data['CartID'] ?? null,
            'address' => $address,
            'calculation_date' => now()->toISOString(),
            'source' => 'taxcloud',
        ];
    }

    /**
     * Get TIC (Tax Item Code) recommendations for MSP services
     * 
     * @param string $serviceDescription Description of the service
     * @return array TIC recommendations
     */
    public function getTicRecommendations(string $serviceDescription): array
    {
        $keywords = strtolower($serviceDescription);
        $recommendations = [];

        $ticDatabase = [
            // Computer and Digital Services
            '30070' => [
                'description' => 'Computer Services',
                'keywords' => ['managed', 'monitoring', 'support', 'maintenance', 'computer', 'it services', 'help desk'],
                'examples' => ['Managed IT Services', 'Network Monitoring', 'Help Desk Support'],
            ],
            '30240' => [
                'description' => 'Prewritten Computer Software',
                'keywords' => ['software', 'application', 'program', 'license'],
                'examples' => ['Software Licenses', 'Application Subscriptions'],
            ],
            '30070' => [
                'description' => 'Custom Computer Programming Services',
                'keywords' => ['custom', 'development', 'programming', 'coding'],
                'examples' => ['Custom Software Development', 'Programming Services'],
            ],
            // Telecommunications
            '10115' => [
                'description' => 'Telecommunications Services',
                'keywords' => ['phone', 'voip', 'telephone', 'calling', 'communication'],
                'examples' => ['VoIP Services', 'Phone Systems', 'Telecommunications'],
            ],
            '10400' => [
                'description' => 'Internet Access Services',
                'keywords' => ['internet', 'broadband', 'connectivity', 'access'],
                'examples' => ['Internet Access', 'Broadband Services'],
            ],
            // Cloud and Hosting
            '30070' => [
                'description' => 'Data Processing Services',
                'keywords' => ['cloud', 'hosting', 'storage', 'backup', 'data'],
                'examples' => ['Cloud Hosting', 'Data Storage', 'Backup Services'],
            ],
            // Equipment
            '00000' => [
                'description' => 'General Merchandise',
                'keywords' => ['equipment', 'hardware', 'device', 'router', 'server'],
                'examples' => ['Network Equipment', 'Computer Hardware'],
            ],
        ];

        foreach ($ticDatabase as $tic => $info) {
            $score = 0;
            
            foreach ($info['keywords'] as $keyword) {
                if (strpos($keywords, $keyword) !== false) {
                    $score++;
                }
            }
            
            if ($score > 0) {
                $recommendations[] = [
                    'tic' => $tic,
                    'description' => $info['description'],
                    'score' => $score,
                    'examples' => $info['examples'],
                    'confidence' => min(100, ($score / count($info['keywords'])) * 100),
                ];
            }
        }

        // Sort by score (highest first)
        usort($recommendations, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'service_description' => $serviceDescription,
            'recommendations' => array_slice($recommendations, 0, 3), // Top 3 recommendations
            'default_tic' => $recommendations[0]['tic'] ?? '30070', // Default to Computer Services
            'source' => 'taxcloud_tic_database',
        ];
    }

    /**
     * Get configuration status for TaxCloud integration
     */
    public function getConfigurationStatus(): array
    {
        return [
            'configured' => $this->hasValidCredentials(),
            'api_login_id_set' => !empty($this->apiLoginId),
            'api_key_set' => !empty($this->apiKey),
            'customer_id_set' => !empty($this->customerId),
            'base_url' => $this->baseUrl,
            'origin_address_configured' => !empty(env('COMPANY_ADDRESS1')),
            'source' => 'taxcloud_config',
        ];
    }
}