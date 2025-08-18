<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Quote;
use App\Models\QuoteApproval;
use App\Models\QuoteVersion;
use App\Models\QuoteTemplate;
use App\Models\InvoiceItem;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Carbon\Carbon;

/**
 * QuoteService
 * 
 * Enterprise quote management service with VoIP-specific features,
 * approval workflows, versioning, and automated processes.
 */
class QuoteService
{
    /**
     * Create a new quote
     */
    public function createQuote(Client $client, array $data): Quote
    {
        return DB::transaction(function () use ($client, $data) {
            $quote = Quote::create([
                'company_id' => Auth::user()->company_id,
                'client_id' => $client->id,
                'category_id' => $data['category_id'] ?? null,
                'prefix' => $data['prefix'] ?? 'QTE',
                'scope' => $data['scope'] ?? null,
                'date' => $data['date'] ?? now(),
                'expire_date' => $data['expire_date'] ?? now()->addDays(30),
                'valid_until' => $data['valid_until'] ?? now()->addDays(30),
                'status' => $data['status'] ?? Quote::STATUS_DRAFT,
                'approval_status' => $data['approval_status'] ?? Quote::APPROVAL_PENDING,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_type' => $data['discount_type'] ?? Quote::DISCOUNT_FIXED,
                'currency_code' => $data['currency_code'] ?? 'USD',
                'note' => $data['note'] ?? null,
                'terms_conditions' => $data['terms_conditions'] ?? null,
                'auto_renew' => $data['auto_renew'] ?? false,
                'auto_renew_days' => $data['auto_renew_days'] ?? null,
                'template_name' => $data['template_name'] ?? null,
                'voip_config' => $data['voip_config'] ?? null,
                'pricing_model' => $data['pricing_model'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Add items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $itemData) {
                    $this->addQuoteItem($quote, $itemData);
                }
            }

            // Calculate totals after adding items
            $quote->calculateTotals();

            // Create initial version snapshot
            QuoteVersion::createSnapshot($quote, [], 'Initial quote creation');

            // Initialize approval workflow if needed
            if ($this->requiresApproval($quote)) {
                $this->initializeApprovalWorkflow($quote);
            } else {
                $quote->update(['approval_status' => Quote::APPROVAL_NOT_REQUIRED]);
            }

            Log::info('Quote created', [
                'quote_id' => $quote->id,
                'client_id' => $client->id,
                'user_id' => Auth::id()
            ]);

            return $quote;
        });
    }

    /**
     * Update an existing quote
     */
    public function updateQuote(Quote $quote, array $data): Quote
    {
        return DB::transaction(function () use ($quote, $data) {
            // Capture changes for version tracking
            $originalData = $quote->toArray();
            
            $quote->update($data);

            // Update items if provided
            if (isset($data['items'])) {
                $this->updateQuoteItems($quote, $data['items']);
            }

            // Create version snapshot if significant changes
            $changes = $this->detectSignificantChanges($originalData, $quote->fresh()->toArray());
            if (!empty($changes)) {
                QuoteVersion::createSnapshot($quote, $changes, $data['change_reason'] ?? 'Quote updated');
            }

            // Recalculate totals
            $quote->calculateTotals();

            Log::info('Quote updated', [
                'quote_id' => $quote->id,
                'changes' => array_keys($changes),
                'user_id' => Auth::id()
            ]);

            return $quote->fresh();
        });
    }

    /**
     * Add item to quote with VoIP-specific pricing
     */
    public function addQuoteItem(Quote $quote, array $itemData): InvoiceItem
    {
        $itemData['quote_id'] = $quote->id;
        $itemData['order'] = $quote->items()->count() + 1;

        // Apply VoIP-specific pricing if configured
        if ($quote->voip_config && $quote->pricing_model) {
            $itemData = $this->applyVoipPricing($itemData, $quote);
        }

        $item = InvoiceItem::create($itemData);
        
        // Recalculate quote totals
        $quote->calculateTotals();

        Log::info('Quote item added', [
            'quote_id' => $quote->id,
            'item_id' => $item->id,
            'user_id' => Auth::id()
        ]);

        return $item;
    }

    /**
     * Update quote item
     */
    public function updateQuoteItem(InvoiceItem $item, array $data): InvoiceItem
    {
        // Apply VoIP-specific pricing if configured
        if ($item->quote && $item->quote->voip_config && $item->quote->pricing_model) {
            $data = $this->applyVoipPricing($data, $item->quote);
        }

        $item->update($data);
        
        // Recalculate quote totals
        $item->quote->calculateTotals();

        Log::info('Quote item updated', [
            'quote_id' => $item->quote_id,
            'item_id' => $item->id,
            'user_id' => Auth::id()
        ]);

        return $item;
    }

    /**
     * Delete quote item
     */
    public function deleteQuoteItem(InvoiceItem $item): void
    {
        $quoteId = $item->quote_id;
        $item->delete();

        // Recalculate quote totals
        if ($item->quote) {
            $item->quote->calculateTotals();
        }

        Log::info('Quote item deleted', [
            'quote_id' => $quoteId,
            'item_id' => $item->id,
            'user_id' => Auth::id()
        ]);
    }

    /**
     * Calculate quote totals with VoIP-specific pricing
     */
    public function calculateQuoteTotals(Quote $quote): array
    {
        $subtotal = $quote->getSubtotal();
        $discountAmount = $quote->getDiscountAmount();
        $taxAmount = $quote->getTotalTax();
        
        // Apply VoIP-specific calculations
        if ($quote->voip_config && $quote->pricing_model) {
            $voipCalculations = $this->calculateVoipSpecificCosts($quote);
            $subtotal += $voipCalculations['additional_costs'];
        }

        $total = $subtotal - $discountAmount + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discountAmount,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'voip_breakdown' => $voipCalculations ?? null,
        ];
    }

    /**
     * Submit quote for approval
     */
    public function submitForApproval(Quote $quote): bool
    {
        if (!$this->requiresApproval($quote)) {
            $quote->update(['approval_status' => Quote::APPROVAL_NOT_REQUIRED]);
            return true;
        }

        // Initialize approval workflow
        $this->initializeApprovalWorkflow($quote);

        // Create version snapshot
        QuoteVersion::createSnapshot($quote, [], 'Submitted for approval');

        Log::info('Quote submitted for approval', [
            'quote_id' => $quote->id,
            'user_id' => Auth::id()
        ]);

        return true;
    }

    /**
     * Process approval at specific level
     */
    public function processApproval(Quote $quote, string $level, string $action, string $comments = null): bool
    {
        return DB::transaction(function () use ($quote, $level, $action, $comments) {
            $approval = QuoteApproval::where('quote_id', $quote->id)
                ->where('approval_level', $level)
                ->first();

            if (!$approval) {
                throw new \Exception("No approval found for level: {$level}");
            }

            if ($action === 'approve') {
                $approval->approve($comments);
                $this->advanceApprovalWorkflow($quote);
            } else {
                $approval->reject($comments);
                $quote->update(['approval_status' => Quote::APPROVAL_REJECTED]);
            }

            // Create version snapshot
            QuoteVersion::createSnapshot($quote, [
                'approval_action' => [
                    'old' => $approval->status,
                    'new' => $action,
                    'level' => $level,
                    'comments' => $comments
                ]
            ], "Quote {$action}d at {$level} level");

            Log::info('Quote approval processed', [
                'quote_id' => $quote->id,
                'level' => $level,
                'action' => $action,
                'user_id' => Auth::id()
            ]);

            return true;
        });
    }

    /**
     * Send quote to client
     */
    public function sendQuote(Quote $quote): bool
    {
        if (!$quote->isFullyApproved() && $quote->approval_status !== Quote::APPROVAL_NOT_REQUIRED) {
            throw new \Exception('Quote must be approved before sending');
        }

        $quote->markAsSent();

        // Schedule follow-up reminders
        $this->scheduleFollowUps($quote);

        // TODO: Send email to client
        // $this->emailService->sendQuoteEmail($quote);

        Log::info('Quote sent to client', [
            'quote_id' => $quote->id,
            'client_id' => $quote->client_id,
            'user_id' => Auth::id()
        ]);

        return true;
    }

    /**
     * Convert quote to invoice
     */
    public function convertToInvoice(Quote $quote): Invoice
    {
        if (!$quote->isAccepted()) {
            throw new \Exception('Quote must be accepted before converting to invoice');
        }

        return DB::transaction(function () use ($quote) {
            $invoice = $quote->convertToInvoice();

            // Create version snapshot
            QuoteVersion::createSnapshot($quote, [
                'converted_to_invoice' => [
                    'old' => null,
                    'new' => $invoice->id
                ]
            ], 'Quote converted to invoice');

            Log::info('Quote converted to invoice', [
                'quote_id' => $quote->id,
                'invoice_id' => $invoice->id,
                'user_id' => Auth::id()
            ]);

            return $invoice;
        });
    }

    /**
     * Create quote revision
     */
    public function createRevision(Quote $quote, array $changes = [], string $reason = null): Quote
    {
        return DB::transaction(function () use ($quote, $changes, $reason) {
            $revision = $quote->createRevision($changes);

            // Create version snapshot for original quote
            QuoteVersion::createSnapshot($quote, [
                'revision_created' => [
                    'old' => null,
                    'new' => $revision->id
                ]
            ], 'Revision created');

            Log::info('Quote revision created', [
                'original_quote_id' => $quote->id,
                'revision_quote_id' => $revision->id,
                'user_id' => Auth::id()
            ]);

            return $revision;
        });
    }

    /**
     * Duplicate existing quote
     */
    public function duplicateQuote(Quote $quote, array $overrides = []): Quote
    {
        return DB::transaction(function () use ($quote, $overrides) {
            $newQuote = $quote->replicate();
            
            // Reset specific fields
            $newQuote->number = null; // Will be auto-generated
            $newQuote->version = 1;
            $newQuote->status = Quote::STATUS_DRAFT;
            $newQuote->approval_status = Quote::APPROVAL_PENDING;
            $newQuote->url_key = null;
            $newQuote->sent_at = null;
            $newQuote->viewed_at = null;
            $newQuote->accepted_at = null;
            $newQuote->declined_at = null;
            $newQuote->parent_quote_id = null;
            $newQuote->converted_invoice_id = null;
            $newQuote->created_by = Auth::id();
            $newQuote->approved_by = null;

            // Apply overrides
            foreach ($overrides as $key => $value) {
                $newQuote->$key = $value;
            }

            $newQuote->save();

            // Duplicate items
            foreach ($quote->items as $item) {
                $newQuote->items()->create([
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'price' => $item->price,
                    'discount' => $item->discount,
                    'order' => $item->order,
                    'tax_id' => $item->tax_id,
                    'category_id' => $item->category_id,
                    'product_id' => $item->product_id,
                ]);
            }

            $newQuote->calculateTotals();

            // Create initial version snapshot
            QuoteVersion::createSnapshot($newQuote, [], 'Quote duplicated');

            Log::info('Quote duplicated', [
                'original_quote_id' => $quote->id,
                'new_quote_id' => $newQuote->id,
                'user_id' => Auth::id()
            ]);

            return $newQuote;
        });
    }

    /**
     * Create quote from template
     */
    public function createFromTemplate(QuoteTemplate $template, Client $client, array $customizations = []): Quote
    {
        return DB::transaction(function () use ($template, $client, $customizations) {
            $quote = $template->createQuote($client, $customizations);

            Log::info('Quote created from template', [
                'template_id' => $template->id,
                'quote_id' => $quote->id,
                'client_id' => $client->id,
                'user_id' => Auth::id()
            ]);

            return $quote;
        });
    }

    /**
     * Process expired quotes
     */
    public function processExpiredQuotes(): int
    {
        $expiredCount = 0;

        Quote::where('status', Quote::STATUS_SENT)
            ->where(function ($query) {
                $query->where('expire_date', '<', now())
                      ->orWhere('valid_until', '<', now());
            })
            ->chunk(50, function ($quotes) use (&$expiredCount) {
                foreach ($quotes as $quote) {
                    // Check for auto-renewal
                    if ($quote->auto_renew) {
                        $renewedQuote = $quote->autoRenew();
                        if ($renewedQuote) {
                            Log::info('Quote auto-renewed', [
                                'original_quote_id' => $quote->id,
                                'renewed_quote_id' => $renewedQuote->id
                            ]);
                            continue;
                        }
                    }

                    // Mark as expired
                    $quote->update(['status' => Quote::STATUS_EXPIRED]);
                    $expiredCount++;

                    Log::info('Quote marked as expired', [
                        'quote_id' => $quote->id
                    ]);
                }
            });

        return $expiredCount;
    }

    /**
     * Get quote statistics
     */
    public function getQuoteStatistics(int $companyId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Quote::where('company_id', $companyId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $quotes = $query->get();

        return [
            'total_quotes' => $quotes->count(),
            'draft_quotes' => $quotes->where('status', Quote::STATUS_DRAFT)->count(),
            'sent_quotes' => $quotes->where('status', Quote::STATUS_SENT)->count(),
            'accepted_quotes' => $quotes->where('status', Quote::STATUS_ACCEPTED)->count(),
            'declined_quotes' => $quotes->where('status', Quote::STATUS_DECLINED)->count(),
            'expired_quotes' => $quotes->where('status', Quote::STATUS_EXPIRED)->count(),
            'converted_quotes' => $quotes->where('status', Quote::STATUS_CONVERTED)->count(),
            'total_value' => $quotes->sum('amount'),
            'accepted_value' => $quotes->where('status', Quote::STATUS_ACCEPTED)->sum('amount'),
            'conversion_rate' => $quotes->where('status', Quote::STATUS_SENT)->count() > 0 
                ? ($quotes->where('status', Quote::STATUS_ACCEPTED)->count() / $quotes->where('status', Quote::STATUS_SENT)->count()) * 100 
                : 0,
            'average_quote_value' => $quotes->count() > 0 ? $quotes->avg('amount') : 0,
        ];
    }

    /**
     * Check if quote requires approval based on amount and company policies
     */
    private function requiresApproval(Quote $quote): bool
    {
        // This would typically check company-specific approval thresholds
        // For now, we'll use a simple amount-based rule
        $managerApprovalThreshold = 5000;
        $executiveApprovalThreshold = 25000;

        return $quote->amount >= $managerApprovalThreshold;
    }

    /**
     * Initialize approval workflow
     */
    private function initializeApprovalWorkflow(Quote $quote): void
    {
        $amount = $quote->amount;
        $managerApprovalThreshold = 5000;
        $executiveApprovalThreshold = 25000;

        if ($amount >= $managerApprovalThreshold) {
            QuoteApproval::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'user_id' => $this->getApproverForLevel('manager', $quote->company_id),
                'approval_level' => QuoteApproval::LEVEL_MANAGER,
                'status' => QuoteApproval::STATUS_PENDING,
            ]);
        }

        if ($amount >= $executiveApprovalThreshold) {
            QuoteApproval::create([
                'company_id' => $quote->company_id,
                'quote_id' => $quote->id,
                'user_id' => $this->getApproverForLevel('executive', $quote->company_id),
                'approval_level' => QuoteApproval::LEVEL_EXECUTIVE,
                'status' => QuoteApproval::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Advance approval workflow to next level
     */
    private function advanceApprovalWorkflow(Quote $quote): void
    {
        $pendingApprovals = $quote->approvals()->where('status', QuoteApproval::STATUS_PENDING)->count();
        $approvedApprovals = $quote->approvals()->where('status', QuoteApproval::STATUS_APPROVED)->count();
        $totalApprovals = $quote->approvals()->count();

        if ($pendingApprovals === 0 && $approvedApprovals === $totalApprovals) {
            // All approvals completed
            $quote->update([
                'approval_status' => Quote::APPROVAL_EXECUTIVE_APPROVED,
                'approved_by' => Auth::id()
            ]);
        } elseif ($quote->approvals()->where('approval_level', QuoteApproval::LEVEL_MANAGER)->where('status', QuoteApproval::STATUS_APPROVED)->exists()) {
            // Manager approved
            $quote->update(['approval_status' => Quote::APPROVAL_MANAGER_APPROVED]);
        }
    }

    /**
     * Get approver user ID for specific level
     */
    private function getApproverForLevel(string $level, int $companyId): ?int
    {
        // This would typically query company hierarchy or role assignments
        // For now, return null - would need to implement proper role-based assignment
        return null;
    }

    /**
     * Apply VoIP-specific pricing calculations
     */
    private function applyVoipPricing(array $itemData, Quote $quote): array
    {
        $pricingModel = $quote->pricing_model;
        $voipConfig = $quote->voip_config;

        if (!$pricingModel || !$voipConfig) {
            return $itemData;
        }

        // Apply usage-based pricing
        if (isset($pricingModel['type']) && $pricingModel['type'] === Quote::PRICING_USAGE_BASED) {
            // Calculate based on usage allowances and overages
            $basePrice = $itemData['price'] ?? 0;
            $quantity = $itemData['quantity'] ?? 1;
            
            // Add overage charges if applicable
            if (isset($voipConfig['monthly_allowances']) && isset($pricingModel['overage_rates'])) {
                // This would calculate overages based on usage patterns
                // Implementation would depend on specific business rules
            }
        }

        return $itemData;
    }

    /**
     * Calculate VoIP-specific costs
     */
    private function calculateVoipSpecificCosts(Quote $quote): array
    {
        $voipConfig = $quote->voip_config;
        $pricingModel = $quote->pricing_model;

        $calculations = [
            'setup_fees' => $pricingModel['setup_fee'] ?? 0,
            'monthly_recurring' => $pricingModel['monthly_recurring'] ?? 0,
            'equipment_costs' => 0,
            'additional_costs' => 0,
        ];

        // Calculate equipment costs
        if (isset($voipConfig['equipment'])) {
            foreach ($voipConfig['equipment'] as $equipment => $quantity) {
                if (isset($pricingModel['equipment_pricing'][$equipment])) {
                    $calculations['equipment_costs'] += $pricingModel['equipment_pricing'][$equipment] * $quantity;
                }
            }
        }

        $calculations['additional_costs'] = $calculations['setup_fees'] + $calculations['equipment_costs'];

        return $calculations;
    }

    /**
     * Detect significant changes between versions
     */
    private function detectSignificantChanges(array $original, array $current): array
    {
        $significantFields = [
            'amount', 'discount_amount', 'expire_date', 'valid_until', 
            'note', 'terms_conditions', 'status', 'approval_status'
        ];

        $changes = [];
        foreach ($significantFields as $field) {
            if (isset($original[$field]) && isset($current[$field]) && $original[$field] !== $current[$field]) {
                $changes[$field] = [
                    'old' => $original[$field],
                    'new' => $current[$field]
                ];
            }
        }

        return $changes;
    }

    /**
     * Update quote items
     */
    private function updateQuoteItems(Quote $quote, array $items): void
    {
        // Remove existing items
        $quote->items()->delete();

        // Add new items
        foreach ($items as $order => $item) {
            $item['quote_id'] = $quote->id;
            $item['order'] = $order + 1;
            
            InvoiceItem::create($item);
        }
    }

    /**
     * Schedule follow-up reminders
     */
    private function scheduleFollowUps(Quote $quote): void
    {
        // This would integrate with a job queue or notification system
        // to schedule follow-up reminders at specific intervals
        
        Log::info('Follow-up reminders scheduled', [
            'quote_id' => $quote->id,
            'expire_date' => $quote->expire_date ?? $quote->valid_until
        ]);
    }
}