<?php

namespace App\Domains\Client\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

class ClientRecurringInvoice extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'client_id',
        'template_name',
        'description',
        'amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'currency',
        'frequency',
        'interval_count',
        'start_date',
        'end_date',
        'next_invoice_date',
        'last_invoice_date',
        'day_of_month',
        'day_of_week',
        'status',
        'auto_send',
        'payment_terms_days',
        'late_fee_percentage',
        'late_fee_flat_amount',
        'invoice_prefix',
        'invoice_notes',
        'payment_instructions',
        'line_items',
        'invoice_count',
        'total_revenue',
        'metadata',
        'created_by',
        'paused_at',
        'paused_reason',
        'cancelled_at',
        'cancelled_reason'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
        'late_fee_flat_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_invoice_date' => 'date',
        'last_invoice_date' => 'date',
        'auto_send' => 'boolean',
        'line_items' => 'array',
        'metadata' => 'array',
        'paused_at' => 'datetime',
        'cancelled_at' => 'datetime'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'next_invoice_date',
        'last_invoice_date',
        'paused_at',
        'cancelled_at'
    ];

    /**
     * Get the client that owns the recurring invoice
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the recurring invoice
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get all generated invoices from this recurring invoice
     */
    public function invoices()
    {
        return $this->hasMany(ClientInvoice::class, 'recurring_invoice_id');
    }

    /**
     * Scope for active recurring invoices
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
                    ->whereNull('cancelled_at')
                    ->whereNull('paused_at');
    }

    /**
     * Scope for paused recurring invoices
     */
    public function scopePaused($query)
    {
        return $query->where('status', 'paused')
                    ->whereNotNull('paused_at');
    }

    /**
     * Scope for cancelled recurring invoices
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled')
                    ->whereNotNull('cancelled_at');
    }

    /**
     * Scope for due recurring invoices (ready to generate)
     */
    public function scopeDue($query)
    {
        return $query->active()
                    ->where('next_invoice_date', '<=', now()->toDateString());
    }

    /**
     * Scope for upcoming due invoices (next 30 days)
     */
    public function scopeUpcoming($query, $days = 30)
    {
        return $query->active()
                    ->whereBetween('next_invoice_date', [
                        now()->toDateString(),
                        now()->addDays($days)->toDateString()
                    ]);
    }

    /**
     * Get available frequencies
     */
    public static function getFrequencies()
    {
        return [
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'biweekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semiannually' => 'Semi-annually',
            'annually' => 'Annually',
            'custom' => 'Custom Interval'
        ];
    }

    /**
     * Get available statuses
     */
    public static function getStatuses()
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'paused' => 'Paused',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired'
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
            'INR' => 'Indian Rupee (₹)'
        ];
    }

    /**
     * Check if recurring invoice is active
     */
    public function isActive()
    {
        return $this->status === 'active' 
            && is_null($this->cancelled_at) 
            && is_null($this->paused_at);
    }

    /**
     * Check if recurring invoice is paused
     */
    public function isPaused()
    {
        return $this->status === 'paused' && !is_null($this->paused_at);
    }

    /**
     * Check if recurring invoice is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled' && !is_null($this->cancelled_at);
    }

    /**
     * Check if recurring invoice is due for generation
     */
    public function isDue()
    {
        return $this->isActive() 
            && $this->next_invoice_date 
            && $this->next_invoice_date->lte(now()->toDate());
    }

    /**
     * Check if recurring invoice has expired (past end date)
     */
    public function isExpired()
    {
        return $this->end_date && $this->end_date->lt(now()->toDate());
    }

    /**
     * Get the formatted amount
     */
    public function getFormattedAmountAttribute()
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->amount, 2);
    }

    /**
     * Get the formatted total amount
     */
    public function getFormattedTotalAmountAttribute()
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->total_amount, 2);
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
            'INR' => '₹'
        ];

        return $symbols[$this->currency] ?? $this->currency;
    }

    /**
     * Get frequency description
     */
    public function getFrequencyDescriptionAttribute()
    {
        $base = self::getFrequencies()[$this->frequency] ?? $this->frequency;
        
        if ($this->frequency === 'custom' && $this->interval_count > 1) {
            return "Every {$this->interval_count} " . str_plural('day', $this->interval_count);
        }
        
        if ($this->interval_count > 1) {
            return "Every {$this->interval_count} " . str_plural(strtolower($base), $this->interval_count);
        }
        
        return $base;
    }

    /**
     * Get next invoice date in human readable format
     */
    public function getNextInvoiceDateHumanAttribute()
    {
        if (!$this->next_invoice_date) {
            return 'Not scheduled';
        }
        
        return $this->next_invoice_date->format('M j, Y');
    }

    /**
     * Get days until next invoice
     */
    public function getDaysUntilNextInvoiceAttribute()
    {
        if (!$this->next_invoice_date) {
            return null;
        }
        
        return now()->diffInDays($this->next_invoice_date, false);
    }

    /**
     * Get revenue summary
     */
    public function getRevenueSummaryAttribute()
    {
        return [
            'invoice_count' => $this->invoice_count ?? 0,
            'total_revenue' => $this->total_revenue ?? 0,
            'average_per_invoice' => $this->invoice_count > 0 ? ($this->total_revenue / $this->invoice_count) : 0
        ];
    }

    /**
     * Calculate next invoice date based on frequency
     */
    public function calculateNextInvoiceDate($fromDate = null)
    {
        if (!$fromDate) {
            $fromDate = $this->last_invoice_date ?: $this->start_date;
        }
        
        if (!$fromDate) {
            return null;
        }
        
        $date = Carbon::parse($fromDate);
        
        switch ($this->frequency) {
            case 'daily':
                $date->addDays($this->interval_count ?: 1);
                break;
            case 'weekly':
                $date->addWeeks($this->interval_count ?: 1);
                if ($this->day_of_week) {
                    $date->next($this->day_of_week);
                }
                break;
            case 'biweekly':
                $date->addWeeks(2 * ($this->interval_count ?: 1));
                break;
            case 'monthly':
                $date->addMonths($this->interval_count ?: 1);
                if ($this->day_of_month) {
                    $date->day(min($this->day_of_month, $date->daysInMonth));
                }
                break;
            case 'quarterly':
                $date->addMonths(3 * ($this->interval_count ?: 1));
                break;
            case 'semiannually':
                $date->addMonths(6 * ($this->interval_count ?: 1));
                break;
            case 'annually':
                $date->addYears($this->interval_count ?: 1);
                break;
            case 'custom':
                $date->addDays($this->interval_count ?: 1);
                break;
        }
        
        return $date->toDate();
    }

    /**
     * Update next invoice date
     */
    public function updateNextInvoiceDate()
    {
        $this->next_invoice_date = $this->calculateNextInvoiceDate();
        $this->save();
        
        return $this->next_invoice_date;
    }

    /**
     * Pause the recurring invoice
     */
    public function pause($reason = null)
    {
        $this->status = 'paused';
        $this->paused_at = now();
        $this->paused_reason = $reason;
        $this->save();
        
        return true;
    }

    /**
     * Resume the recurring invoice
     */
    public function resume()
    {
        $this->status = 'active';
        $this->paused_at = null;
        $this->paused_reason = null;
        $this->updateNextInvoiceDate();
        $this->save();
        
        return true;
    }

    /**
     * Cancel the recurring invoice
     */
    public function cancel($reason = null)
    {
        $this->status = 'cancelled';
        $this->cancelled_at = now();
        $this->cancelled_reason = $reason;
        $this->save();
        
        return true;
    }

    /**
     * Generate an invoice from this recurring invoice
     */
    public function generateInvoice()
    {
        if (!$this->isDue()) {
            return false;
        }

        // Create invoice data
        $invoiceData = [
            'client_id' => $this->client_id,
            'recurring_invoice_id' => $this->id,
            'invoice_number' => $this->generateInvoiceNumber(),
            'description' => $this->description,
            'amount' => $this->amount,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount,
            'currency' => $this->currency,
            'issue_date' => now()->toDate(),
            'due_date' => now()->addDays($this->payment_terms_days ?: 30)->toDate(),
            'status' => 'pending',
            'notes' => $this->invoice_notes,
            'payment_instructions' => $this->payment_instructions,
            'line_items' => $this->line_items,
            'auto_send' => $this->auto_send,
            'tenant_id' => $this->tenant_id
        ];

        $invoice = ClientInvoice::create($invoiceData);

        // Update recurring invoice counters
        $this->increment('invoice_count');
        $this->increment('total_revenue', $this->total_amount);
        $this->last_invoice_date = now()->toDate();
        $this->updateNextInvoiceDate();

        // Check if expired
        if ($this->isExpired()) {
            $this->status = 'expired';
            $this->save();
        }

        return $invoice;
    }

    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber()
    {
        $prefix = $this->invoice_prefix ?: 'REC';
        $count = $this->invoice_count + 1;
        
        return $prefix . '-' . str_pad($count, 4, '0', STR_PAD_LEFT);
    }
}