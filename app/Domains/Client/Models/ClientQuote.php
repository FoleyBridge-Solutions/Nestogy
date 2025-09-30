<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientQuote extends Model
{
    use BelongsToCompany, HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'quote_number',
        'title',
        'description',
        'amount',
        'tax_rate',
        'tax_amount',
        'discount_amount',
        'discount_type',
        'total_amount',
        'currency',
        'status',
        'valid_until',
        'issued_date',
        'accepted_date',
        'declined_date',
        'converted_date',
        'invoice_id',
        'terms_conditions',
        'notes',
        'payment_terms',
        'delivery_timeframe',
        'project_scope',
        'line_items',
        'attachments',
        'client_signature',
        'signature_date',
        'signature_ip',
        'conversion_probability',
        'follow_up_date',
        'competitor_quotes',
        'win_loss_reason',
        'metadata',
        'created_by',
        'approved_by',
        'approved_at',
        'sent_at',
        'viewed_at',
        'last_reminder_sent',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'conversion_probability' => 'decimal:2',
        'valid_until' => 'date',
        'issued_date' => 'date',
        'accepted_date' => 'date',
        'declined_date' => 'date',
        'converted_date' => 'date',
        'follow_up_date' => 'date',
        'signature_date' => 'datetime',
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'last_reminder_sent' => 'datetime',
        'line_items' => 'array',
        'attachments' => 'array',
        'competitor_quotes' => 'array',
        'metadata' => 'array',
    ];

    protected $dates = [
        'valid_until',
        'issued_date',
        'accepted_date',
        'declined_date',
        'converted_date',
        'follow_up_date',
        'signature_date',
        'approved_at',
        'sent_at',
        'viewed_at',
        'last_reminder_sent',
    ];

    /**
     * Get the client that owns the quote
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the quote
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved the quote
     */
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Get the invoice created from this quote
     */
    public function invoice()
    {
        return $this->belongsTo(ClientInvoice::class, 'invoice_id');
    }

    /**
     * Scope for draft quotes
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    /**
     * Scope for pending quotes
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for sent quotes
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * Scope for accepted quotes
     */
    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Scope for declined quotes
     */
    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    /**
     * Scope for expired quotes
     */
    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    /**
     * Scope for converted quotes
     */
    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    /**
     * Scope for quotes expiring soon
     */
    public function scopeExpiringSoon($query, $days = 7)
    {
        return $query->whereIn('status', ['sent', 'pending'])
            ->where('valid_until', '>=', now()->toDate())
            ->where('valid_until', '<=', now()->addDays($days)->toDate());
    }

    /**
     * Scope for overdue quotes (past valid_until)
     */
    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['sent', 'pending'])
            ->where('valid_until', '<', now()->toDate());
    }

    /**
     * Scope for follow-up due quotes
     */
    public function scopeFollowUpDue($query)
    {
        return $query->whereIn('status', ['sent', 'pending'])
            ->where('follow_up_date', '<=', now()->toDate());
    }

    /**
     * Get available statuses
     */
    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'pending' => 'Pending Approval',
            'sent' => 'Sent to Client',
            'viewed' => 'Viewed by Client',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'expired' => 'Expired',
            'converted' => 'Converted to Invoice',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get available currencies
     */
    public static function getCurrencies()
    {
        return [
            'USD' => 'US Dollar ($)',
            'EUR' => 'Euro (€)',
            'GBP' => 'British Pound (£)',
            'CAD' => 'Canadian Dollar (C$)',
            'AUD' => 'Australian Dollar (A$)',
            'JPY' => 'Japanese Yen (¥)',
            'CNY' => 'Chinese Yuan (¥)',
            'INR' => 'Indian Rupee (₹)',
        ];
    }

    /**
     * Get discount types
     */
    public static function getDiscountTypes()
    {
        return [
            'percentage' => 'Percentage',
            'fixed' => 'Fixed Amount',
        ];
    }

    /**
     * Check if quote is draft
     */
    public function isDraft()
    {
        return $this->status === 'draft';
    }

    /**
     * Check if quote is pending approval
     */
    public function isPending()
    {
        return $this->status === 'pending';
    }

    /**
     * Check if quote is sent
     */
    public function isSent()
    {
        return $this->status === 'sent';
    }

    /**
     * Check if quote is accepted
     */
    public function isAccepted()
    {
        return $this->status === 'accepted';
    }

    /**
     * Check if quote is declined
     */
    public function isDeclined()
    {
        return $this->status === 'declined';
    }

    /**
     * Check if quote is expired
     */
    public function isExpired()
    {
        return $this->status === 'expired' ||
               ($this->valid_until && $this->valid_until->lt(now()->toDate()));
    }

    /**
     * Check if quote is converted
     */
    public function isConverted()
    {
        return $this->status === 'converted';
    }

    /**
     * Check if quote is expiring soon
     */
    public function isExpiringSoon($days = 7)
    {
        return $this->valid_until &&
               $this->valid_until->gte(now()->toDate()) &&
               $this->valid_until->lte(now()->addDays($days)->toDate()) &&
               in_array($this->status, ['sent', 'pending']);
    }

    /**
     * Check if quote needs follow up
     */
    public function needsFollowUp()
    {
        return $this->follow_up_date &&
               $this->follow_up_date->lte(now()->toDate()) &&
               in_array($this->status, ['sent', 'pending']);
    }

    /**
     * Get the formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($this->amount, 2);
    }

    /**
     * Get the formatted total amount
     */
    public function getFormattedTotalAmountAttribute()
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($this->total_amount, 2);
    }

    /**
     * Get the formatted discount amount
     */
    public function getFormattedDiscountAmountAttribute()
    {
        if ($this->discount_type === 'percentage') {
            return $this->discount_amount.'%';
        }

        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($this->discount_amount, 2);
    }

    /**
     * Get currency symbol
     */
    public function getCurrencySymbol()
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Get days until expiry
     */
    public function getDaysUntilExpiryAttribute()
    {
        if (! $this->valid_until) {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }

    /**
     * Get human readable expiry status
     */
    public function getExpiryStatusAttribute()
    {
        if (! $this->valid_until) {
            return 'No expiry date';
        }

        $days = $this->days_until_expiry;

        if ($days < 0) {
            return 'Expired '.abs($days).' days ago';
        } elseif ($days == 0) {
            return 'Expires today';
        } elseif ($days == 1) {
            return 'Expires tomorrow';
        } else {
            return 'Expires in '.$days.' days';
        }
    }

    /**
     * Get conversion rate attribute
     */
    public function getConversionRateAttribute()
    {
        return $this->conversion_probability ?? 0;
    }

    /**
     * Generate quote number
     */
    public static function generateQuoteNumber($prefix = 'QUO')
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        $lastQuote = static::where('quote_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('quote_number', 'desc')
            ->first();

        if ($lastQuote) {
            $lastNumber = (int) substr($lastQuote->quote_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix.'-'.$year.$month.'-'.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calculate totals based on line items
     */
    public function calculateTotals()
    {
        $subtotal = 0;

        if ($this->line_items && is_array($this->line_items)) {
            foreach ($this->line_items as $item) {
                $itemTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
                $subtotal += $itemTotal;
            }
        }

        $this->amount = $subtotal;

        // Calculate discount
        $discountAmount = 0;
        if ($this->discount_amount > 0) {
            if ($this->discount_type === 'percentage') {
                $discountAmount = ($subtotal * $this->discount_amount) / 100;
            } else {
                $discountAmount = $this->discount_amount;
            }
        }

        $afterDiscount = $subtotal - $discountAmount;

        // Calculate tax
        $taxAmount = 0;
        if ($this->tax_rate > 0) {
            $taxAmount = ($afterDiscount * $this->tax_rate) / 100;
        }

        $this->tax_amount = $taxAmount;
        $this->total_amount = $afterDiscount + $taxAmount;

        return $this;
    }

    /**
     * Send quote to client
     */
    public function send()
    {
        if ($this->isDraft() || $this->isPending()) {
            $this->status = 'sent';
            $this->sent_at = now();
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Mark quote as viewed
     */
    public function markAsViewed()
    {
        if ($this->isSent()) {
            $this->status = 'viewed';
            $this->viewed_at = now();
            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Accept the quote
     */
    public function accept($signatureData = null)
    {
        if (in_array($this->status, ['sent', 'viewed'])) {
            $this->status = 'accepted';
            $this->accepted_date = now()->toDate();

            if ($signatureData) {
                $this->client_signature = $signatureData['signature'] ?? null;
                $this->signature_date = now();
                $this->signature_ip = $signatureData['ip'] ?? null;
            }

            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Decline the quote
     */
    public function decline($reason = null)
    {
        if (in_array($this->status, ['sent', 'viewed'])) {
            $this->status = 'declined';
            $this->declined_date = now()->toDate();

            if ($reason) {
                $this->win_loss_reason = $reason;
            }

            $this->save();

            return true;
        }

        return false;
    }

    /**
     * Convert quote to invoice
     */
    public function convertToInvoice()
    {
        if ($this->isAccepted()) {
            // Create invoice data
            $invoiceData = [
                'client_id' => $this->client_id,
                'quote_id' => $this->id,
                'invoice_number' => ClientInvoice::generateInvoiceNumber(),
                'description' => $this->description,
                'amount' => $this->amount,
                'tax_rate' => $this->tax_rate,
                'tax_amount' => $this->tax_amount,
                'discount_amount' => $this->discount_amount,
                'discount_type' => $this->discount_type,
                'total_amount' => $this->total_amount,
                'currency' => $this->currency,
                'issue_date' => now()->toDate(),
                'due_date' => now()->addDays(30)->toDate(),
                'status' => 'pending',
                'notes' => $this->notes,
                'payment_terms' => $this->payment_terms,
                'line_items' => $this->line_items,
                'company_id' => $this->company_id,
            ];

            $invoice = ClientInvoice::create($invoiceData);

            // Update quote status
            $this->status = 'converted';
            $this->converted_date = now()->toDate();
            $this->invoice_id = $invoice->id;
            $this->save();

            return $invoice;
        }

        return false;
    }

    /**
     * Check and update expired quotes
     */
    public static function updateExpiredQuotes()
    {
        return static::whereIn('status', ['sent', 'viewed'])
            ->where('valid_until', '<', now()->toDate())
            ->update(['status' => 'expired']);
    }
}
