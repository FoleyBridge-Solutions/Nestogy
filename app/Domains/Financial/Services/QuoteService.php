<?php

namespace App\Domains\Financial\Services;

use App\Domains\Financial\Exceptions\QuoteNotFoundException;
use App\Domains\Financial\Exceptions\QuotePermissionException;
use App\Domains\Financial\Exceptions\QuoteValidationException;
use App\Domains\Financial\Services\TaxEngine\TaxEngineRouter;
use App\Models\Category;
use App\Models\Client;
use App\Models\Product;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Enhanced Quote Service with proper transaction handling
 * Handles all quote business logic with comprehensive error handling
 */
class QuoteService
{
    protected ?TaxEngineRouter $taxEngine = null;

    /**
     * Get or create tax engine router instance
     */
    protected function getTaxEngine(): TaxEngineRouter
    {
        if (! $this->taxEngine) {
            $companyId = auth()->user()->company_id ?? 1;
            $this->taxEngine = new TaxEngineRouter($companyId);
        }

        return $this->taxEngine;
    }

    /**
     * Create a new quote with transaction handling
     *
     * @throws QuoteValidationException
     */
    public function createQuote(array $data): Quote
    {
        $this->validateQuoteData($data);

        return DB::transaction(function () use ($data) {
            try {
                // Create the main quote record
                $quote = Quote::create([
                    'client_id' => $data['client_id'],
                    'category_id' => $data['category_id'],
                    'company_id' => auth()->user()->company_id,
                    'date' => $data['date'] ?? now(),
                    'expire' => $data['expire_date'],
                    'status' => $data['status'] ?? Quote::STATUS_DRAFT,
                    'currency_code' => $data['currency_code'] ?? 'USD',
                    'scope' => $data['scope'] ?? '',
                    'note' => $data['note'] ?? '',
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'amount' => 0, // Use amount instead of total_amount
                    'number' => $this->generateQuoteNumber(),
                    'url_key' => \Str::random(32),
                ]);

                // Add quote items if provided
                if (! empty($data['items'])) {
                    $skipComplex = $data['skip_complex_calculations'] ?? false;
                    $this->addQuoteItems($quote, $data['items'], $skipComplex);
                }

                // Skip complex tax calculations for now to avoid transaction issues
                // TODO: Re-enable when tax system is properly configured
                // if (($data['status'] ?? Quote::STATUS_DRAFT) !== Quote::STATUS_DRAFT) {
                //     $this->recalculateAllTaxes($quote);
                // }

                // Calculate pricing (always run full calculation for final totals)
                $this->calculatePricing($quote);

                // Log quote creation
                Log::info('Quote created successfully', [
                    'quote_id' => $quote->id,
                    'client_id' => $quote->client_id,
                    'total_amount' => $quote->amount,
                    'user_id' => auth()->id(),
                ]);

                // Clear relevant caches
                $this->clearQuoteCaches($quote);

                return $this->loadQuoteWithRelations($quote);

            } catch (\Exception $e) {
                Log::error('Quote creation failed', [
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'user_id' => auth()->id(),
                ]);
                throw new QuoteValidationException('Failed to create quote: '.$e->getMessage());
            }
        });
    }

    /**
     * Update an existing quote with transaction handling
     *
     * @throws QuoteValidationException
     * @throws QuotePermissionException
     */
    public function updateQuote(Quote $quote, array $data): Quote
    {
        $this->ensureUserCanModifyQuote($quote);
        $this->validateQuoteData($data, $quote->id);

        return DB::transaction(function () use ($quote, $data) {
            try {
                // Store original data for audit
                $originalData = $quote->toArray();

                // Update quote fields
                $quote->update([
                    'client_id' => $data['client_id'] ?? $quote->client_id,
                    'category_id' => $data['category_id'] ?? $quote->category_id,
                    'date' => $data['date'] ?? $quote->date,
                    'expire' => $data['expire_date'] ?? $quote->expire,
                    'status' => $data['status'] ?? $quote->status,
                    'currency_code' => $data['currency_code'] ?? $quote->currency_code,
                    'scope' => $data['scope'] ?? $quote->scope,
                    'note' => $data['note'] ?? $quote->note,
                    'discount_amount' => $data['discount_amount'] ?? $quote->discount_amount,
                ]);

                // Update items if provided
                if (isset($data['items'])) {
                    $this->updateQuoteItems($quote, $data['items']);
                }

                // Recalculate pricing
                $this->calculatePricing($quote);

                // Log quote update
                Log::info('Quote updated successfully', [
                    'quote_id' => $quote->id,
                    'changes' => array_diff_assoc($quote->toArray(), $originalData),
                    'user_id' => auth()->id(),
                ]);

                // Clear relevant caches
                $this->clearQuoteCaches($quote);

                return $this->loadQuoteWithRelations($quote);

            } catch (\Exception $e) {
                Log::error('Quote update failed', [
                    'quote_id' => $quote->id,
                    'error' => $e->getMessage(),
                    'data' => $data,
                    'user_id' => auth()->id(),
                ]);
                throw new QuoteValidationException('Failed to update quote: '.$e->getMessage());
            }
        });
    }

    /**
     * Delete a quote with proper cleanup
     *
     * @throws QuotePermissionException
     */
    public function deleteQuote(Quote $quote): bool
    {
        $this->ensureUserCanModifyQuote($quote);

        return DB::transaction(function () use ($quote) {
            try {
                // Store ID for logging
                $quoteId = $quote->id;

                // Delete related items first
                $quote->items()->delete();

                // Soft delete the quote
                $quote->delete();

                // Log quote deletion
                Log::info('Quote deleted successfully', [
                    'quote_id' => $quoteId,
                    'user_id' => auth()->id(),
                ]);

                // Clear relevant caches
                $this->clearQuoteCaches($quote);

                return true;

            } catch (\Exception $e) {
                Log::error('Quote deletion failed', [
                    'quote_id' => $quote->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);
                throw new QuoteValidationException('Failed to delete quote: '.$e->getMessage());
            }
        });
    }

    /**
     * Duplicate an existing quote
     *
     * @throws QuotePermissionException
     */
    public function duplicateQuote(Quote $originalQuote, array $overrides = []): Quote
    {
        $this->ensureUserCanViewQuote($originalQuote);

        return DB::transaction(function () use ($originalQuote, $overrides) {
            try {
                // Prepare data for new quote
                $quoteData = $originalQuote->toArray();

                // Remove fields that shouldn't be duplicated
                unset($quoteData['id'], $quoteData['number'], $quoteData['created_at'],
                    $quoteData['updated_at'], $quoteData['archived_at']);

                // Apply overrides
                $quoteData = array_merge($quoteData, $overrides);

                // Set new date and status
                $quoteData['date'] = now();
                $quoteData['status'] = Quote::STATUS_DRAFT;

                // Create new quote
                $newQuote = $this->createQuote($quoteData);

                // Duplicate items
                $originalItems = $originalQuote->items()->get();
                $itemsData = $originalItems->map(function ($item) {
                    $itemData = $item->toArray();
                    unset($itemData['id'], $itemData['quote_id'], $itemData['created_at'], $itemData['updated_at']);

                    return $itemData;
                })->toArray();

                if (! empty($itemsData)) {
                    $this->addQuoteItems($newQuote, $itemsData);
                    $this->calculatePricing($newQuote);
                }

                Log::info('Quote duplicated successfully', [
                    'original_quote_id' => $originalQuote->id,
                    'new_quote_id' => $newQuote->id,
                    'user_id' => auth()->id(),
                ]);

                return $newQuote->fresh(['client', 'category', 'items', 'user']);

            } catch (\Exception $e) {
                Log::error('Quote duplication failed', [
                    'original_quote_id' => $originalQuote->id,
                    'error' => $e->getMessage(),
                    'user_id' => auth()->id(),
                ]);
                throw new QuoteValidationException('Failed to duplicate quote: '.$e->getMessage());
            }
        });
    }

    /**
     * Add items to a quote with advanced tax calculations
     */
    public function addQuoteItems(Quote $quote, array $items, bool $skipComplex = false): Collection
    {
        $createdItems = [];
        $client = $quote->client;

        foreach ($items as $index => $itemData) {
            // Calculate subtotal before tax
            $quantity = $itemData['quantity'] ?? 1;
            $unitPrice = $itemData['price'] ?? $itemData['unit_price'] ?? 0;
            $discount = $itemData['discount'] ?? 0;
            $subtotal = ($quantity * $unitPrice) - $discount;

            // Determine service type and category for tax calculation
            $serviceType = $this->determineServiceType($itemData);
            $categoryId = $itemData['category_id'] ?? null;

            // Calculate taxes using tax engine (with optional skip)
            $taxCalculation = $this->calculateItemTax([
                'base_price' => $unitPrice,
                'quantity' => $quantity,
                'discount' => $discount,
                'category_id' => $categoryId,
                'category_type' => $serviceType,
                'service_data' => $itemData['service_data'] ?? [],
                'customer_id' => $client->id,
                'customer_address' => [
                    'line1' => $client->address,
                    'state' => $client->state,
                    'city' => $client->city,
                    'zip' => $client->zip_code,
                    'country' => $client->country ?? 'US',
                ],
            ], $skipComplex);

            // Create the item with basic information
            $item = $quote->items()->create([
                'company_id' => $quote->company_id,
                'name' => $itemData['name'],
                'description' => $itemData['description'] ?? '',
                'quantity' => $quantity,
                'price' => $unitPrice,
                'discount' => $discount,
                'subtotal' => $subtotal,
                'tax' => $taxCalculation['total_tax_amount'] ?? 0,
                'total' => $subtotal + ($taxCalculation['total_tax_amount'] ?? 0),
                'order' => $itemData['order'] ?? $index + 1,
                'quote_id' => $quote->id,
            ]);

            $createdItems[] = $item;
        }

        return new Collection($createdItems);
    }

    /**
     * Determine service type from item data
     */
    protected function determineServiceType(array $itemData): string
    {
        // Check if service_type is explicitly provided
        if (! empty($itemData['service_type'])) {
            return $itemData['service_type'];
        }

        // Try to determine from product
        if (! empty($itemData['product_id'])) {
            $product = Product::find($itemData['product_id']);
            if ($product && $product->category) {
                $categoryName = strtolower($product->category->name);
                // Map category names to service types
                if (strpos($categoryName, 'voip') !== false || strpos($categoryName, 'hosted') !== false) {
                    return 'voip';
                }
                if (strpos($categoryName, 'telecom') !== false || strpos($categoryName, 'sip') !== false) {
                    return 'telecom';
                }
                if (strpos($categoryName, 'cloud') !== false) {
                    return 'cloud';
                }
                if (strpos($categoryName, 'saas') !== false || strpos($categoryName, 'software') !== false) {
                    return 'saas';
                }
            }
        }

        // Check category name if provided
        if (! empty($itemData['category'])) {
            $category = strtolower($itemData['category']);
            if (strpos($category, 'voip') !== false || strpos($category, 'hosted') !== false) {
                return 'voip';
            }
            if (strpos($category, 'telecom') !== false) {
                return 'telecom';
            }
        }

        // Default to general service
        return 'general';
    }

    /**
     * Calculate tax for a single item
     */
    protected function calculateItemTax(array $params, bool $skipComplex = false): array
    {
        $basePrice = $params['base_price'] ?? 0;
        $quantity = $params['quantity'] ?? 1;
        $discount = $params['discount'] ?? 0;
        $subtotal = ($basePrice * $quantity) - $discount;

        // Quick calculation for speed during drafts
        if ($skipComplex) {
            // Use simple 8% tax rate for drafts
            $simpleTax = $subtotal * 0.08;

            return [
                'total_tax_amount' => $simpleTax,
                'effective_tax_rate' => 8.0,
                'tax_breakdown' => [
                    ['name' => 'Estimated Tax', 'rate' => 8.0, 'amount' => $simpleTax],
                ],
            ];
        }

        try {
            // Try the complex tax engine first
            return $this->getTaxEngine()->calculateTaxes($params);
        } catch (\Exception $e) {
            Log::warning('Tax calculation failed for quote item, using fallback', [
                'error' => $e->getMessage(),
                'params' => $params,
            ]);

            // Fallback to database tax rates
            $taxRate = $this->getFallbackTaxRate($params);
            $taxAmount = $subtotal * ($taxRate / 100);

            return [
                'total_tax_amount' => $taxAmount,
                'effective_tax_rate' => $taxRate,
                'tax_breakdown' => [
                    ['name' => 'Sales Tax', 'rate' => $taxRate, 'amount' => $taxAmount],
                ],
            ];
        }
    }

    /**
     * Get fallback tax rate from database
     */
    protected function getFallbackTaxRate(array $params): float
    {
        try {
            // Get the first active tax rate for the company
            $tax = \App\Models\Tax::where('company_id', auth()->user()->company_id)
                ->whereNull('archived_at')
                ->orderBy('percent', 'desc')
                ->first();

            return $tax ? $tax->percent : 8.25; // Default to 8.25% if no tax found
        } catch (\Exception $e) {
            Log::warning('Fallback tax rate lookup failed', ['error' => $e->getMessage()]);

            return 8.25; // Default tax rate
        }
    }

    /**
     * Get tax jurisdiction ID for a client
     */
    protected function getJurisdictionId(Client $client): ?int
    {
        try {
            // Try to find jurisdiction based on client's state
            if ($client->state) {
                $jurisdiction = \App\Models\TaxJurisdiction::where('company_id', $client->company_id)
                    ->where('state_code', $client->state)
                    ->where('is_active', true)
                    ->first();

                if ($jurisdiction) {
                    return $jurisdiction->id;
                }
            }

            // Fallback to federal jurisdiction
            $federalJurisdiction = \App\Models\TaxJurisdiction::where('company_id', $client->company_id)
                ->where('jurisdiction_type', 'federal')
                ->where('is_active', true)
                ->first();

            return $federalJurisdiction?->id;
        } catch (\Exception $e) {
            Log::warning('Failed to get tax jurisdiction', [
                'client_id' => $client->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update quote items
     */
    public function updateQuoteItems(Quote $quote, array $items): Collection
    {
        // Delete existing items
        $quote->items()->delete();

        // Add new items
        return $this->addQuoteItems($quote, $items);
    }

    /**
     * Calculate quote pricing with advanced tax calculations
     */
    public function calculatePricing(Quote $quote): Quote
    {
        $items = $quote->items()->get();

        // Calculate subtotal from individual item subtotals
        $subtotal = $items->sum('subtotal');

        // Calculate discount
        $discountAmount = 0;
        if ($quote->discount_amount > 0) {
            if ($quote->discount_type === Quote::DISCOUNT_PERCENTAGE) {
                $discountAmount = $subtotal * ($quote->discount_amount / 100);
            } else {
                $discountAmount = min($quote->discount_amount, $subtotal);
            }
        }

        // Calculate tax using new tax engine data
        $taxAmount = $items->sum('tax'); // Use tax calculated by tax engine

        // Skip tax recalculation for now to avoid transaction issues
        // TODO: Re-enable when tax system is properly configured
        // if ($taxAmount == 0 && $items->where('tax_breakdown', null)->count() > 0) {
        //     $taxAmount = $this->recalculateTaxesForItems($quote, $items);
        // }

        // Calculate total
        $totalAmount = $subtotal - $discountAmount + $taxAmount;

        // Update quote amount (note: using 'amount' field as seen in migrations)
        $quote->update([
            'amount' => $totalAmount,
        ]);

        return $quote;
    }

    /**
     * Recalculate taxes for items that don't have tax breakdown
     * (Backward compatibility for existing quotes)
     */
    protected function recalculateTaxesForItems(Quote $quote, Collection $items): float
    {
        $totalTax = 0;
        $client = $quote->client;

        foreach ($items as $item) {
            // Skip if item already has tax breakdown
            if ($item->tax_breakdown) {
                $totalTax += $item->tax;

                continue;
            }

            // Determine service type
            $serviceType = $item->service_type ?? $this->determineServiceTypeFromItem($item);

            // Calculate tax using tax engine
            $taxCalculation = $this->calculateItemTax([
                'base_price' => $item->price,
                'quantity' => $item->quantity,
                'discount' => $item->discount,
                'category_id' => $item->category_id,
                'category_type' => $serviceType,
                'service_data' => $item->service_data ?? [],
                'customer_id' => $client->id,
                'customer_address' => [
                    'line1' => $client->address,
                    'state' => $client->state,
                    'city' => $client->city,
                    'zip' => $client->zip_code,
                    'country' => $client->country ?? 'US',
                ],
            ]);

            $itemTax = $taxCalculation['total_tax_amount'] ?? 0;
            $totalTax += $itemTax;

            // Update item with new tax information
            $item->update([
                'tax' => $itemTax,
                'tax_breakdown' => $taxCalculation['tax_breakdown'] ?? null,
                'tax_rate' => $taxCalculation['effective_tax_rate'] ?? 0,
                'service_type' => $serviceType,
                'tax_jurisdiction_id' => $this->getJurisdictionId($client),
                'total' => $item->subtotal + $itemTax,
            ]);
        }

        return $totalTax;
    }

    /**
     * Recalculate all taxes for quote items using full tax engine
     * Used when converting draft to final quote
     */
    protected function recalculateAllTaxes(Quote $quote): void
    {
        $items = $quote->items()->get();
        $client = $quote->client;

        foreach ($items as $item) {
            // Determine service type and category for tax calculation
            $serviceType = $this->determineServiceType([
                'product_id' => $item->product_id,
                'service_id' => $item->service_id,
                'bundle_id' => $item->bundle_id,
                'service_type' => $item->service_type,
            ]);

            // Recalculate taxes using full tax engine (no skip)
            $taxCalculation = $this->calculateItemTax([
                'base_price' => $item->price,
                'quantity' => $item->quantity,
                'discount' => $item->discount,
                'category_id' => $item->category_id,
                'category_type' => $serviceType,
                'service_data' => $item->service_data ?? [],
                'customer_id' => $client->id,
                'customer_address' => [
                    'line1' => $client->address,
                    'state' => $client->state,
                    'city' => $client->city,
                    'zip' => $client->zip_code,
                    'country' => $client->country ?? 'US',
                ],
            ], false); // false = don't skip complex calculations

            // Update item with accurate tax information
            $item->update([
                'tax' => $taxCalculation['total_tax_amount'] ?? 0,
                'tax_breakdown' => $taxCalculation['tax_breakdown'] ?? null,
                'tax_rate' => $taxCalculation['effective_tax_rate'] ?? 0,
                'service_type' => $serviceType,
                'tax_jurisdiction_id' => $this->getJurisdictionId($client),
                'total' => $item->subtotal + ($taxCalculation['total_tax_amount'] ?? 0),
            ]);
        }
    }

    /**
     * Determine service type from existing item data
     */
    protected function determineServiceTypeFromItem($item): string
    {
        // Try to get from product relationship
        if ($item->product_id && $item->product) {
            return $this->determineServiceType(['product_id' => $item->product_id]);
        }

        // Try to get from category
        if ($item->category_id) {
            return $this->determineServiceType(['category_id' => $item->category_id]);
        }

        // Check item name for clues
        $name = strtolower($item->name);
        if (strpos($name, 'voip') !== false || strpos($name, 'hosted') !== false) {
            return 'voip';
        }
        if (strpos($name, 'telecom') !== false || strpos($name, 'phone') !== false) {
            return 'telecom';
        }

        return 'general';
    }

    /**
     * Get quotes for the current user's company with caching
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getQuotes(array $filters = [], int $perPage = 15)
    {
        $cacheKey = 'quotes_'.auth()->user()->company_id.'_'.md5(serialize($filters)).'_'.$perPage;

        return Cache::remember($cacheKey, 300, function () use ($filters, $perPage) {
            $query = Quote::where('company_id', auth()->user()->company_id)
                ->with(['client', 'category', 'items']);

            // Apply filters
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (! empty($filters['client_id'])) {
                $query->where('client_id', $filters['client_id']);
            }

            if (! empty($filters['date_from'])) {
                $query->whereDate('date', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $query->whereDate('date', '<=', $filters['date_to']);
            }

            if (! empty($filters['search'])) {
                $search = '%'.$filters['search'].'%';
                $query->where(function ($q) use ($search) {
                    $q->where('number', 'like', $search)
                        ->orWhere('scope', 'like', $search)
                        ->orWhereHas('client', function ($clientQuery) use ($search) {
                            $clientQuery->where('name', 'like', $search)
                                ->orWhere('company_name', 'like', $search);
                        });
                });
            }

            // Default ordering
            $query->orderBy('created_at', 'desc');

            return $query->paginate($perPage);
        });
    }

    /**
     * Find a quote by ID with permission check
     *
     * @throws QuoteNotFoundException
     * @throws QuotePermissionException
     */
    public function findQuote(int $id): Quote
    {
        $quote = Quote::with(['client', 'category', 'items', 'creator'])->find($id);

        if (! $quote) {
            throw new QuoteNotFoundException("Quote with ID {$id} not found.");
        }

        $this->ensureUserCanViewQuote($quote);

        return $quote;
    }

    /**
     * Generate a unique quote number
     */
    public function generateQuoteNumber(): string
    {
        $prefix = config('app.quote_prefix', 'QUO');
        $year = date('Y');
        $month = date('m');

        $lastQuote = Quote::where('company_id', auth()->user()->company_id)
            ->whereRaw('EXTRACT(YEAR FROM created_at) = ?', [$year])
            ->whereRaw('EXTRACT(MONTH FROM created_at) = ?', [$month])
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastQuote ? (int) substr($lastQuote->number, -4) + 1 : 1;

        return sprintf('%s-%s%s-%04d', $prefix, $year, $month, $sequence);
    }

    /**
     * Auto-save quote data
     */
    public function autoSave(array $data): array
    {
        try {
            $cacheKey = 'autosave_quote_'.auth()->id().'_'.($data['quote_id'] ?? 'new');

            Cache::put($cacheKey, $data, 3600); // Save for 1 hour

            return [
                'success' => true,
                'message' => 'Quote auto-saved successfully',
                'timestamp' => now()->toISOString(),
            ];
        } catch (\Exception $e) {
            Log::warning('Quote auto-save failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return [
                'success' => false,
                'message' => 'Auto-save failed',
            ];
        }
    }

    /**
     * Get auto-saved quote data
     */
    public function getAutoSavedData(?int $quoteId = null): ?array
    {
        $cacheKey = 'autosave_quote_'.auth()->id().'_'.($quoteId ?? 'new');

        return Cache::get($cacheKey);
    }

    /**
     * Validate quote data
     *
     * @throws QuoteValidationException
     */
    private function validateQuoteData(array $data, ?int $quoteId = null): void
    {
        $rules = [
            'client_id' => 'required|exists:clients,id',
            'category_id' => 'required|exists:categories,id',
            'date' => 'required|date',
            'expire_date' => 'nullable|date|after:date',
            'currency_code' => 'required|string|size:3',
            'discount_amount' => 'nullable|numeric|min:0',
            'items' => 'nullable|array',
            'items.*.name' => 'required_with:items|string|max:255',
            'items.*.quantity' => 'required_with:items|numeric|min:0.01',
            'items.*.price' => 'required_with:items|numeric|min:0',
        ];

        $messages = [
            'client_id.required' => 'Please select a client.',
            'client_id.exists' => 'The selected client is invalid.',
            'category_id.required' => 'Please select a category.',
            'category_id.exists' => 'The selected category is invalid.',
            'date.before_or_equal' => 'The quote date cannot be in the future.',
            'expire_date.after' => 'The expiration date must be after the quote date.',
        ];

        $validator = Validator::make($data, $rules, $messages);

        // Add custom validation
        $validator->after(function ($validator) use ($data) {
            // Validate client belongs to user's company
            if (! empty($data['client_id'])) {
                $client = Client::find($data['client_id']);
                if ($client && $client->company_id !== auth()->user()->company_id) {
                    $validator->errors()->add('client_id', 'The selected client is invalid.');
                }
            }

            // Validate category belongs to user's company
            if (! empty($data['category_id'])) {
                $category = Category::find($data['category_id']);
                if ($category && $category->company_id !== auth()->user()->company_id) {
                    $validator->errors()->add('category_id', 'The selected category is invalid.');
                }
            }

            // Validate discount percentage
            if (! empty($data['discount_type']) && $data['discount_type'] === Quote::DISCOUNT_PERCENTAGE) {
                if (! empty($data['discount_amount']) && $data['discount_amount'] > 100) {
                    $validator->errors()->add('discount_amount', 'Discount percentage cannot exceed 100%.');
                }
            }
        });

        if ($validator->fails()) {
            throw new QuoteValidationException('Quote validation failed.', $validator->errors()->toArray());
        }
    }

    /**
     * Ensure user can view quote
     *
     * @throws QuotePermissionException
     */
    private function ensureUserCanViewQuote(Quote $quote): void
    {
        if ($quote->company_id !== auth()->user()->company_id) {
            throw new QuotePermissionException('You do not have permission to view this quote.');
        }
    }

    /**
     * Ensure user can modify quote
     *
     * @throws QuotePermissionException
     */
    private function ensureUserCanModifyQuote(Quote $quote): void
    {
        $this->ensureUserCanViewQuote($quote);

        // Add additional permission checks here if needed
        // For example, check if quote is locked, user role, etc.
        if ($quote->status === Quote::STATUS_ACCEPTED) {
            throw new QuotePermissionException('Cannot modify an accepted quote.');
        }
    }

    /**
     * Clear relevant caches
     */
    private function clearQuoteCaches(Quote $quote): void
    {
        $companyId = $quote->company_id;

        // Clear quotes list cache
        Cache::forget("quotes_{$companyId}_*");

        // Clear specific quote cache
        Cache::forget("quote_{$quote->id}");

        // Clear client quotes cache
        Cache::forget("client_quotes_{$quote->client_id}");
    }

    /**
     * Load quote with optimized relations
     * Prevents N+1 query problems by eager loading all necessary relationships
     */
    public function loadQuoteWithRelations(Quote $quote): Quote
    {
        return $quote->fresh([
            'client:id,name,company_name,email,phone',
            'category:id,name,color',
            'items' => function ($query) {
                $query->select(['id', 'quote_id', 'product_id', 'name', 'description',
                    'quantity', 'price', 'discount', 'subtotal', 'tax', 'total',
                    'order', 'category_id', 'service_type'])
                    ->orderBy('order');
            },
            'items.product:id,name,sku,category_id',
            'creator:id,name,email',
        ]);
    }

    /**
     * Get quotes for a company with optimized queries
     *
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getCompanyQuotes(int $companyId, array $filters = [], int $perPage = 15)
    {
        $cacheKey = "quotes_{$companyId}_".md5(serialize($filters))."_page_{$perPage}";

        return Cache::remember($cacheKey, 300, function () use ($companyId, $filters, $perPage) {
            $query = Quote::where('company_id', $companyId)
                ->with([
                    'client:id,name,company_name,email',
                    'category:id,name,color',
                    'items:id,quote_id,name,quantity,price,subtotal',
                ])
                ->select(['id', 'number', 'client_id', 'category_id',
                    'date', 'expire', 'status', 'amount',
                    'created_at', 'updated_at']);

            // Apply filters
            if (! empty($filters['status'])) {
                $query->where('status', $filters['status']);
            } else {
                // By default, exclude cancelled quotes unless specifically filtering for them
                $query->where('status', '!=', Quote::STATUS_CANCELLED);
            }

            if (! empty($filters['client_id'])) {
                $query->where('client_id', $filters['client_id']);
            }

            if (! empty($filters['category_id'])) {
                $query->where('category_id', $filters['category_id']);
            }

            if (! empty($filters['date_from'])) {
                $query->where('date', '>=', $filters['date_from']);
            }

            if (! empty($filters['date_to'])) {
                $query->where('date', '<=', $filters['date_to']);
            }

            if (! empty($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('number', 'like', '%'.$filters['search'].'%')
                        ->orWhereHas('client', function ($clientQuery) use ($filters) {
                            $clientQuery->where('name', 'like', '%'.$filters['search'].'%')
                                ->orWhere('company_name', 'like', '%'.$filters['search'].'%');
                        });
                });
            }

            return $query->latest('created_at')->paginate($perPage);
        });
    }

    /**
     * Get client quotes with optimized loading
     */
    public function getClientQuotes(int $clientId, int $limit = 10): Collection
    {
        $cacheKey = "client_quotes_{$clientId}_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($clientId, $limit) {
            return Quote::where('client_id', $clientId)
                ->with([
                    'category:id,name,color',
                    'items' => function ($query) {
                        $query->select(['id', 'quote_id', 'name', 'quantity', 'price', 'subtotal']);
                    },
                ])
                ->select(['id', 'number', 'category_id', 'date', 'expire',
                    'status', 'amount', 'created_at'])
                ->latest('created_at')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get quote statistics for dashboard with single optimized query
     */
    public function getQuoteStatistics(int $companyId): array
    {
        $cacheKey = "quote_stats_{$companyId}";

        return Cache::remember($cacheKey, 600, function () use ($companyId) {
            $stats = DB::table('quotes')
                ->selectRaw("
                    COUNT(*) as total_quotes,
                    COUNT(CASE WHEN status = 'Draft' THEN 1 END) as draft_quotes,
                    COUNT(CASE WHEN status = 'Sent' THEN 1 END) as sent_quotes,
                    COUNT(CASE WHEN status = 'Accepted' THEN 1 END) as accepted_quotes,
                    COUNT(CASE WHEN status = 'Declined' THEN 1 END) as declined_quotes,
                    SUM(CASE WHEN status = 'Sent' THEN amount ELSE 0 END) as pending_value,
                    SUM(CASE WHEN status = 'Accepted' THEN amount ELSE 0 END) as accepted_value,
                    AVG(amount) as avg_quote_value
                ")
                ->where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('status', '!=', 'Cancelled') // Exclude cancelled quotes from statistics
                ->first();

            // Get monthly trends with a separate optimized query
            $monthlyStats = DB::table('quotes')
                ->selectRaw('
                    TO_CHAR(created_at, \'YYYY-MM\') as month,
                    COUNT(*) as count,
                    SUM(amount) as total_value
                ')
                ->where('company_id', $companyId)
                ->whereNull('archived_at')
                ->where('status', '!=', 'Cancelled') // Exclude cancelled quotes from trends
                ->where('created_at', '>=', now()->subMonths(12))
                ->groupBy('month')
                ->orderBy('month')
                ->get();

            return [
                'totals' => $stats,
                'monthly_trends' => $monthlyStats,
                'conversion_rate' => $stats->sent_quotes > 0
                    ? round(($stats->accepted_quotes / $stats->sent_quotes) * 100, 2)
                    : 0,
            ];
        });
    }

    /**
     * Bulk update quote statuses with single query
     *
     * @return int Number of updated quotes
     */
    public function bulkUpdateStatus(array $quoteIds, string $status, int $companyId): int
    {
        $updated = Quote::whereIn('id', $quoteIds)
            ->where('company_id', $companyId)
            ->update([
                'status' => $status,
                'updated_at' => now(),
            ]);

        // Clear relevant caches
        Cache::forget("quotes_{$companyId}_*");

        return $updated;
    }

    /**
     * Prepare quote data for copying
     * Extracts all quote data including items for use in create form
     *
     * @throws QuotePermissionException
     */
    public function prepareQuoteForCopy(Quote $sourceQuote): array
    {
        $this->ensureUserCanViewQuote($sourceQuote);

        // Load the quote with all necessary relationships
        $sourceQuote->load(['client', 'category', 'items']);

        // Prepare base quote data
        $quoteData = [
            'copy_from_quote_id' => $sourceQuote->id,
            'copy_from_quote_number' => $sourceQuote->getFullNumber(),
            'client_id' => $sourceQuote->client_id,
            'category_id' => $sourceQuote->category_id,
            'currency_code' => $sourceQuote->currency_code,
            'scope' => $sourceQuote->scope,
            'note' => $sourceQuote->note,
            'discount_amount' => $sourceQuote->discount_amount,
            'terms_conditions' => $sourceQuote->terms_conditions,
            'template_name' => $sourceQuote->template_name,
            'voip_config' => $sourceQuote->voip_config,
            'pricing_model' => $sourceQuote->pricing_model,

            // Reset fields for new quote
            'date' => now()->format('Y-m-d'),
            'expire_date' => now()->addDays(30)->format('Y-m-d'),
            'status' => Quote::STATUS_DRAFT,

            // Clear timestamps and IDs
            'number' => null, // Will be auto-generated
            'url_key' => null, // Will be auto-generated
            'sent_at' => null,
            'viewed_at' => null,
            'accepted_at' => null,
            'declined_at' => null,
            'created_by' => auth()->id(),
        ];

        // Prepare items data
        $items = [];
        foreach ($sourceQuote->items as $index => $item) {
            $items[] = [
                'product_id' => $item->product_id,
                'service_id' => $item->service_id,
                'bundle_id' => $item->bundle_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'discount' => $item->discount,
                'category_id' => $item->category_id,
                'service_type' => $item->service_type,
                'service_data' => $item->service_data,
                'order' => $index + 1,

                // Preserve calculated values for reference but will be recalculated
                'original_subtotal' => $item->subtotal,
                'original_tax' => $item->tax,
                'original_total' => $item->total,
                'original_tax_rate' => $item->tax_rate,
                'original_tax_breakdown' => $item->tax_breakdown,
            ];
        }

        $quoteData['items'] = $items;

        Log::info('Quote prepared for copying', [
            'source_quote_id' => $sourceQuote->id,
            'source_quote_number' => $sourceQuote->getFullNumber(),
            'items_count' => count($items),
            'user_id' => auth()->id(),
        ]);

        return $quoteData;
    }
}
