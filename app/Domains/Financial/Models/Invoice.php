<?php

namespace App\Domains\Financial\Models;

use App\Domains\Client\Models\Client;
use App\Domains\PhysicalMail\Traits\HasPhysicalMail;
use App\Domains\Ticket\Models\Ticket;
use App\Traits\BelongsToCompany;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Invoice Model
 *
 * Represents client invoices with line items, payments, and status tracking.
 * Supports multi-currency and public access via URL keys.
 *
 * @property int $id
 * @property string|null $prefix
 * @property int $number
 * @property string|null $scope
 * @property string $status
 * @property \Illuminate\Support\Carbon $date
 * @property \Illuminate\Support\Carbon $due
 * @property float $discount_amount
 * @property float $amount
 * @property string $currency_code
 * @property string|null $note
 * @property string|null $url_key
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $archived_at
 * @property int $category_id
 * @property int $client_id
 */
class Invoice extends Model
{
    use BelongsToCompany, HasFactory, HasPhysicalMail, SoftDeletes;

    /**
     * The table associated with the model.
     */
    protected $table = 'invoices';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'company_id',
        'prefix',
        'number',
        'scope',
        'status',
        'is_recurring',
        'recurring_invoice_id',
        'recurring_frequency',
        'next_recurring_date',
        'date',
        'due_date',
        'discount_amount',
        'amount',
        'currency_code',
        'note',
        'url_key',
        'category_id',
        'client_id',
        'project_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'company_id' => 'integer',
        'number' => 'integer',
        'is_recurring' => 'boolean',
        'recurring_invoice_id' => 'integer',
        'next_recurring_date' => 'date',
        'date' => 'date',
        'due' => 'date',
        'due_date' => 'date',
        'discount_amount' => 'decimal:2',
        'amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'archived_at' => 'datetime',
        'category_id' => 'integer',
        'client_id' => 'integer',
        'project_id' => 'integer',
    ];

    /**
     * The name of the "deleted at" column for soft deletes.
     */
    const DELETED_AT = 'archived_at';

    /**
     * Date format for display
     */
    const DATE_FORMAT = 'F j, Y';

    /**
     * Invoice status enumeration
     */
    const STATUS_DRAFT = 'Draft';

    const STATUS_SENT = 'Sent';

    const STATUS_PAID = 'Paid';

    const STATUS_OVERDUE = 'Overdue';

    const STATUS_CANCELLED = 'Cancelled';

    /**
     * Get the client this invoice belongs to.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the category this invoice belongs to.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(\App\Domains\Project\Models\Project::class);
    }

    /**
     * Get the invoice items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get the recurring invoice this invoice was generated from.
     */
    public function recurringInvoice(): BelongsTo
    {
        return $this->belongsTo(Recurring::class, 'recurring_invoice_id');
    }

    /**
     * Get payment applications for this invoice.
     */
    public function paymentApplications()
    {
        return $this->morphMany(PaymentApplication::class, 'applicable')
            ->whereHas('payment', function($query) {
                $query->whereNull('deleted_at');
            });
    }

    public function activePaymentApplications()
    {
        return $this->paymentApplications()->where('is_active', true);
    }

    public function creditApplications()
    {
        return $this->morphMany(ClientCreditApplication::class, 'applicable');
    }

    public function activeCreditApplications()
    {
        return $this->creditApplications()->where('is_active', true);
    }

    /**
     * Get the payments collection as an attribute accessor.
     * Returns a collection of unique Payment models that have been applied to this invoice.
     */
    public function getPaymentsAttribute()
    {
        if (!$this->relationLoaded('paymentApplications')) {
            $this->load('paymentApplications.payment');
        }
        
        return $this->paymentApplications
            ->where('is_active', true)
            ->pluck('payment')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Get tickets associated with this invoice.
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Get time entries associated with this invoice.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(\App\Domains\Ticket\Models\TicketTimeEntry::class);
    }

    /**
     * Get tax exemptions for this invoice's client.
     */
    public function taxExemptions(): HasMany
    {
        return $this->hasMany(TaxExemption::class, 'client_id', 'client_id')
            ->where('company_id', $this->company_id);
    }

    /**
     * Get tax exemption usage records for this invoice.
     */
    public function taxExemptionUsage(): HasMany
    {
        return $this->hasMany(TaxExemptionUsage::class);
    }

    /**
     * Get tax calculations for this invoice.
     */
    public function taxCalculations()
    {
        return $this->morphMany(TaxCalculation::class, 'calculable');
    }

    /**
     * Get the latest tax calculation for this invoice.
     */
    public function latestTaxCalculation()
    {
        return $this->taxCalculations()
            ->where('status', '!=', 'voided')
            ->latest()
            ->first();
    }

    /**
     * Get formatted tax breakdown by jurisdiction.
     */
    public function getFormattedTaxBreakdown(): array
    {
        $taxCalculation = $this->latestTaxCalculation();

        if (! $taxCalculation) {
            return [
                'total_tax' => 0,
                'jurisdictions' => [],
                'breakdown' => [],
                'has_detailed_breakdown' => false,
            ];
        }

        $jurisdictions = $taxCalculation->getJurisdictionBreakdown();
        $breakdown = $taxCalculation->getTaxBreakdownSummary();

        return [
            'total_tax' => $taxCalculation->total_tax_amount,
            'effective_rate' => $taxCalculation->effective_tax_rate,
            'jurisdictions' => $jurisdictions,
            'breakdown' => $breakdown,
            'has_detailed_breakdown' => count($jurisdictions) > 0 || count($breakdown) > 0,
            'calculation' => $taxCalculation,
        ];
    }



    /**
     * Get service address for tax calculation.
     */
    public function getServiceAddress(): array
    {
        if ($this->client) {
            return [
                'address' => $this->client->address,
                'city' => $this->client->city,
                'state' => $this->client->state,
                'state_code' => $this->client->state,
                'zip_code' => $this->client->zip_code,
                'country' => $this->client->country,
            ];
        }

        return [];
    }

    public function hasVoIPServices(): bool
    {
        return false;
    }

    public function voipItems()
    {
        return collect();
    }

    public function getVoIPTaxBreakdown(): array
    {
        return [];
    }

    public function recalculateVoIPTaxes(): void
    {
    }

    public function calculateVoIPTaxes(): array
    {
        return [];
    }

    /**
     * Get compliance report data.
     */
    public function getComplianceReportData(): array
    {
        return [
            'invoice_number' => $this->getFullNumber(),
            'client_name' => $this->client->name ?? 'Unknown',
            'invoice_date' => $this->date->toDateString(),
            'service_address' => $this->getServiceAddress(),
            'exemptions_used' => $this->taxExemptionUsage->map(function ($usage) {
                return [
                    'exemption_name' => $usage->taxExemption->exemption_name ?? 'Unknown',
                    'exemption_type' => $usage->taxExemption->exemption_type ?? 'Unknown',
                    'original_amount' => $usage->original_tax_amount,
                    'exempted_amount' => $usage->exempted_amount,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get the invoice's full number.
     */
    public function getFullNumber(): string
    {
        if ($this->prefix) {
            return $this->prefix.'-'.str_pad($this->number, 4, '0', STR_PAD_LEFT);
        }

        return 'INV-'.str_pad($this->number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Check if invoice is overdue.
     */
    public function isOverdue(): bool
    {
        // If no due_date is set, invoice cannot be overdue
        if (! $this->due_date) {
            return false;
        }

        return $this->status !== self::STATUS_PAID &&
               $this->status !== self::STATUS_CANCELLED &&
               Carbon::now()->gt($this->due_date);
    }

    /**
     * Get the effective display status (shows Overdue if applicable).
     */
    public function getDisplayStatusAttribute(): string
    {
        if ($this->isOverdue() && $this->status === self::STATUS_SENT) {
            return self::STATUS_OVERDUE;
        }

        return $this->status;
    }

    /**
     * Check if invoice is paid.
     */
    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    /**
     * Check if invoice is draft.
     */
    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    /**
     * Get total paid amount.
     */
    public function getTotalPaid(): float
    {
        $paymentApplications = $this->activePaymentApplications()->sum('amount');
        $creditApplications = $this->activeCreditApplications()->sum('amount');
        return round($paymentApplications + $creditApplications, 2);
    }

    /**
     * Get remaining balance.
     */
    public function getBalance(): float
    {
        return round($this->amount - $this->getTotalPaid(), 2);
    }

    /**
     * Check if invoice is fully paid.
     */
    public function isFullyPaid(): bool
    {
        return $this->getBalance() <= 0;
    }

    /**
     * Get subtotal (before discount and tax).
     */
    public function getSubtotal(): float
    {
        return round($this->items()->sum('subtotal'), 2);
    }

    /**
     * Get total tax amount.
     */
    public function getTotalTax(): float
    {
        return round($this->items()->sum('tax'), 2);
    }

    /**
     * Calculate and update invoice totals.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->getSubtotal();
        $tax = $this->getTotalTax();
        $total = $subtotal - $this->discount_amount + $tax;

        $this->update(['amount' => $total]);
    }

    /**
     * Get public URL for client access.
     */
    public function getPublicUrl(): string
    {
        if (! $this->url_key) {
            $this->generateUrlKey();
        }

        return url('/invoice/'.$this->url_key);
    }

    /**
     * Generate URL key for public access.
     */
    public function generateUrlKey(): void
    {
        $this->update(['url_key' => bin2hex(random_bytes(16))]);
    }

    /**
     * Mark invoice as sent.
     */
    public function markAsSent(): void
    {
        $this->update(['status' => self::STATUS_SENT]);
    }

    /**
     * Mark invoice as paid.
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    /**
     * Update payment status based on paid amount.
     */
    public function updatePaymentStatus(): void
    {
        $balance = $this->getBalance();
        
        if ($balance <= 0) {
            $this->update(['status' => self::STATUS_PAID]);
        } elseif ($balance < $this->amount) {
            if ($this->status === self::STATUS_DRAFT) {
                return;
            }
            $this->update(['status' => 'Partial']);
        } elseif ($this->isOverdue()) {
            $this->update(['status' => self::STATUS_OVERDUE]);
        } elseif ($this->status !== self::STATUS_DRAFT && $this->status !== self::STATUS_CANCELLED) {
            $this->update(['status' => self::STATUS_SENT]);
        }
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmount(): string
    {
        return $this->formatCurrency($this->amount);
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalance(): string
    {
        return $this->formatCurrency($this->getBalance());
    }

    /**
     * Format amount with currency.
     */
    public function formatCurrency(float $amount): string
    {
        $symbol = $this->getCurrencySymbol();

        return $symbol.number_format($amount, 2);
    }

    /**
     * Get currency symbol.
     */
    public function getCurrencySymbol(): string
    {
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥',
        ];

        return $symbols[$this->currency_code] ?? $this->currency_code;
    }

    /**
     * Scope to get overdue invoices.
     */
    public function scopeOverdue($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED])
            ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope to get paid invoices.
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    /**
     * Scope to get unpaid invoices.
     */
    public function scopeUnpaid($query)
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Scope to get invoices by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to search invoices.
     */
    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('number', $search)
                ->orWhere('note', 'like', '%'.$search.'%');
        });
    }

    /**
     * Get validation rules for invoice creation.
     */
    public static function getValidationRules(): array
    {
        return [
            'prefix' => 'nullable|string|max:10',
            'number' => 'required|integer|min:1',
            'scope' => 'nullable|string|max:255',
            'status' => 'required|in:Draft,Sent,Paid,Overdue,Cancelled',
            'date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:date',
            'discount_amount' => 'numeric|min:0',
            'currency_code' => 'required|string|size:3',
            'note' => 'nullable|string',
            'category_id' => 'required|integer|exists:categories,id',
            'client_id' => 'required|integer|exists:clients,id',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getAvailableStatuses(): array
    {
        return [
            self::STATUS_DRAFT,
            self::STATUS_SENT,
            self::STATUS_PAID,
            self::STATUS_OVERDUE,
            self::STATUS_CANCELLED,
        ];
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment invoice number
        static::creating(function ($invoice) {
            if (! $invoice->number) {
                $lastInvoice = static::where('client_id', $invoice->client_id)
                    ->where('prefix', $invoice->prefix)
                    ->orderBy('number', 'desc')
                    ->first();

                $invoice->number = $lastInvoice ? $lastInvoice->number + 1 : 1;
            }

            // Generate URL key
            if (! $invoice->url_key) {
                $invoice->url_key = bin2hex(random_bytes(16));
            }
        });
    }

    // Physical Mail Implementation Methods

    /**
     * Get the mailing address for this invoice
     */
    protected function getMailingAddress(): array
    {
        return [
            'firstName' => $this->client->contact_first_name ?? '',
            'lastName' => $this->client->contact_last_name ?? '',
            'companyName' => $this->client->name,
            'addressLine1' => $this->client->address,
            'addressLine2' => $this->client->address2,
            'city' => $this->client->city,
            'provinceOrState' => $this->client->state,
            'postalOrZip' => $this->client->zip_code,
            'country' => $this->client->country_code ?? 'US',
        ];
    }

    /**
     * Get the mail template ID for invoices
     */
    protected function getMailTemplate(): ?string
    {
        return config('physical_mail.templates.invoice');
    }

    /**
     * Get merge variables for the mail template
     */
    protected function getMailMergeVariables(): array
    {
        return [
            'invoice_number' => $this->getFormattedNumber(),
            'invoice_date' => $this->date->format(self::DATE_FORMAT),
            'due_date' => $this->due->format(self::DATE_FORMAT),
            'amount_due' => number_format($this->getBalance(), 2),
            'total_amount' => number_format($this->amount, 2),
            'client_name' => $this->client->name,
            'client_email' => $this->client->email,
            'payment_terms' => $this->getPaymentTerms(),
            'invoice_url' => $this->getPublicUrl(),
        ];
    }

    /**
     * Get the mail type for invoices
     */
    protected function getMailType(): string
    {
        return 'Letter';
    }

    /**
     * Get the client ID for this mailing
     */
    protected function getMailClientId(): ?string
    {
        return $this->client_id;
    }

    /**
     * Get payment terms description
     */
    private function getPaymentTerms(): string
    {
        $days = $this->due->diffInDays($this->date);

        return match ($days) {
            0 => 'Due upon receipt',
            30 => 'Net 30',
            60 => 'Net 60',
            90 => 'Net 90',
            default => "Net {$days}",
        };
    }

    /**
     * Render invoice HTML for physical mail
     */
    public function renderForPhysicalMail(): string
    {
        $html = '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto;">';

        // Header
        $html .= '<div style="border-bottom: 2px solid #333; padding-bottom: 20px; margin-bottom: 30px;">';
        $html .= '<h1 style="color: #333; margin: 0;">INVOICE</h1>';
        $html .= '<div style="float: right; text-align: right;">';
        $html .= '<strong>Invoice #:</strong> '.$this->getFormattedNumber().'<br>';
        $html .= '<strong>Date:</strong> '.$this->date->format(self::DATE_FORMAT).'<br>';
        $html .= '<strong>Due Date:</strong> '.$this->due->format(self::DATE_FORMAT);
        $html .= '</div>';
        $html .= '<div style="clear: both;"></div>';
        $html .= '</div>';

        // From and To sections
        $html .= '<div style="margin-bottom: 30px;">';
        $html .= '<div style="width: 48%; float: left;">';
        $html .= '<strong>From:</strong><br>';
        $html .= config('nestogy.company_name').'<br>';
        $html .= config('nestogy.company_address_line1').'<br>';
        $html .= config('nestogy.company_city').', '.config('nestogy.company_state').' '.config('nestogy.company_postal_code');
        $html .= '</div>';

        $html .= '<div style="width: 48%; float: right;">';
        $html .= '<strong>Bill To:</strong><br>';
        $html .= $this->client->name.'<br>';
        if ($this->client->address) {
            $html .= $this->client->address.'<br>';
        }
        if ($this->client->city) {
            $html .= $this->client->city.', '.$this->client->state.' '.$this->client->zip_code;
        }
        $html .= '</div>';
        $html .= '<div style="clear: both;"></div>';
        $html .= '</div>';

        // Line items table
        $html .= '<table style="width: 100%; border-collapse: collapse; margin-bottom: 30px;">';
        $html .= '<thead>';
        $html .= '<tr style="background-color: #f0f0f0;">';
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Description</th>';
        $html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Quantity</th>';
        $html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Rate</th>';
        $html .= '<th style="padding: 10px; text-align: right; border: 1px solid #ddd;">Amount</th>';
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';

        foreach ($this->items as $item) {
            $html .= '<tr>';
            $html .= '<td style="padding: 10px; border: 1px solid #ddd;">'.$item->description.'</td>';
            $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">'.$item->quantity.'</td>';
            $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">$'.number_format($item->price, 2).'</td>';
            $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">$'.number_format($item->quantity * $item->price, 2).'</td>';
            $html .= '</tr>';
        }

        // Totals
        $html .= '<tr>';
        $html .= '<td colspan="3" style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong>Subtotal:</strong></td>';
        $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">$'.number_format($this->amount - $this->discount_amount, 2).'</td>';
        $html .= '</tr>';

        if ($this->discount_amount > 0) {
            $html .= '<tr>';
            $html .= '<td colspan="3" style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong>Discount:</strong></td>';
            $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd;">-$'.number_format($this->discount_amount, 2).'</td>';
            $html .= '</tr>';
        }

        $html .= '<tr style="background-color: #f0f0f0;">';
        $html .= '<td colspan="3" style="padding: 10px; text-align: right; border: 1px solid #ddd;"><strong>Total Due:</strong></td>';
        $html .= '<td style="padding: 10px; text-align: right; border: 1px solid #ddd; font-size: 1.2em;"><strong>$'.number_format($this->amount, 2).'</strong></td>';
        $html .= '</tr>';

        $html .= '</tbody>';
        $html .= '</table>';

        // Notes
        if ($this->note) {
            $html .= '<div style="margin-top: 30px; padding: 15px; background-color: #f9f9f9; border-left: 3px solid #333;">';
            $html .= '<strong>Notes:</strong><br>';
            $html .= nl2br(e($this->note));
            $html .= '</div>';
        }

        // Payment terms
        $html .= '<div style="margin-top: 30px; text-align: center; color: #666;">';
        $html .= '<p><strong>Payment Terms:</strong> '.$this->getPaymentTerms().'</p>';
        $html .= '<p>Thank you for your business!</p>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }
}
