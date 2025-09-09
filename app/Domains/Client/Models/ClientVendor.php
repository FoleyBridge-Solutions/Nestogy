<?php

namespace App\Domains\Client\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientVendor extends Model
{
    use HasFactory, SoftDeletes, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'client_id',
        'vendor_name',
        'description',
        'vendor_type',
        'category',
        'contact_person',
        'email',
        'phone',
        'website',
        'address_line_1',
        'address_line_2',
        'city',
        'state',
        'zip_code',
        'country',
        'tax_id',
        'account_number',
        'payment_terms',
        'preferred_payment_method',
        'relationship_status',
        'start_date',
        'contract_end_date',
        'contract_value',
        'currency',
        'billing_frequency',
        'last_order_date',
        'total_spent',
        'average_response_time',
        'performance_rating',
        'reliability_rating',
        'cost_rating',
        'overall_rating',
        'is_preferred',
        'is_approved',
        'requires_approval',
        'approval_limit',
        'certifications',
        'insurance_info',
        'backup_contacts',
        'service_areas',
        'specializations',
        'notes',
        'tags',
        'last_review_date',
        'next_review_date',
    ];

    protected $casts = [
        'company_id' => 'integer',
        'client_id' => 'integer',
        'start_date' => 'date',
        'contract_end_date' => 'date',
        'last_order_date' => 'date',
        'contract_value' => 'decimal:2',
        'total_spent' => 'decimal:2',
        'approval_limit' => 'decimal:2',
        'performance_rating' => 'integer',
        'reliability_rating' => 'integer',
        'cost_rating' => 'integer',
        'overall_rating' => 'integer',
        'is_preferred' => 'boolean',
        'is_approved' => 'boolean',
        'requires_approval' => 'boolean',
        'certifications' => 'array',
        'insurance_info' => 'array',
        'backup_contacts' => 'array',
        'service_areas' => 'array',
        'specializations' => 'array',
        'tags' => 'array',
        'last_review_date' => 'date',
        'next_review_date' => 'date',
    ];

    protected $dates = [
        'start_date',
        'contract_end_date',
        'last_order_date',
        'last_review_date',
        'next_review_date',
        'deleted_at',
    ];

    /**
     * Get the client that owns the vendor.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope a query to only include vendors of a specific type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('vendor_type', $type);
    }

    /**
     * Scope a query to only include vendors in a specific category.
     */
    public function scopeInCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope a query to only include preferred vendors.
     */
    public function scopePreferred($query)
    {
        return $query->where('is_preferred', true);
    }

    /**
     * Scope a query to only include approved vendors.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope a query to only include active vendors.
     */
    public function scopeActive($query)
    {
        return $query->where('relationship_status', 'active');
    }

    /**
     * Scope a query to only include vendors with high ratings.
     */
    public function scopeHighRated($query, $minRating = 4)
    {
        return $query->where('overall_rating', '>=', $minRating);
    }

    /**
     * Scope a query to only include vendors needing review.
     */
    public function scopeNeedingReview($query)
    {
        return $query->where(function($q) {
            $q->whereNull('next_review_date')
              ->orWhere('next_review_date', '<=', now());
        });
    }

    /**
     * Scope a query to only include vendors with expiring contracts.
     */
    public function scopeContractsExpiringSoon($query, $days = 90)
    {
        return $query->whereNotNull('contract_end_date')
                    ->whereBetween('contract_end_date', [now(), now()->addDays($days)]);
    }

    /**
     * Check if the vendor needs review.
     */
    public function needsReview()
    {
        return !$this->next_review_date || $this->next_review_date->isPast();
    }

    /**
     * Check if the contract is expiring soon.
     */
    public function isContractExpiringSoon($days = 90)
    {
        return $this->contract_end_date && 
               $this->contract_end_date->isFuture() && 
               $this->contract_end_date->diffInDays(now()) <= $days;
    }

    /**
     * Get the vendor status based on relationship and approval.
     */
    public function getStatusAttribute()
    {
        if (!$this->is_approved) {
            return 'pending_approval';
        }
        
        if ($this->relationship_status === 'active' && $this->is_preferred) {
            return 'preferred';
        }
        
        return $this->relationship_status;
    }

    /**
     * Get the vendor status color for UI.
     */
    public function getStatusColorAttribute()
    {
        switch ($this->status) {
            case 'preferred':
                return 'success';
            case 'active':
                return 'info';
            case 'inactive':
                return 'secondary';
            case 'suspended':
                return 'warning';
            case 'terminated':
                return 'danger';
            case 'pending_approval':
                return 'warning';
            default:
                return 'secondary';
        }
    }

    /**
     * Get the vendor status label for UI.
     */
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'preferred':
                return 'Preferred Vendor';
            case 'active':
                return 'Active';
            case 'inactive':
                return 'Inactive';
            case 'suspended':
                return 'Suspended';
            case 'terminated':
                return 'Terminated';
            case 'pending_approval':
                return 'Pending Approval';
            default:
                return 'Unknown';
        }
    }

    /**
     * Get the average rating across all categories.
     */
    public function getAverageRatingAttribute()
    {
        $ratings = array_filter([
            $this->performance_rating,
            $this->reliability_rating,
            $this->cost_rating
        ]);

        return count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : null;
    }

    /**
     * Get the rating color based on overall rating.
     */
    public function getRatingColorAttribute()
    {
        if (!$this->overall_rating) {
            return 'secondary';
        }

        if ($this->overall_rating >= 4) {
            return 'success';
        } elseif ($this->overall_rating >= 3) {
            return 'warning';
        } else {
            return 'danger';
        }
    }

    /**
     * Get available vendor types.
     */
    public static function getVendorTypes()
    {
        return [
            'supplier' => 'Supplier',
            'service_provider' => 'Service Provider',
            'contractor' => 'Contractor',
            'consultant' => 'Consultant',
            'software_vendor' => 'Software Vendor',
            'hardware_vendor' => 'Hardware Vendor',
            'cloud_provider' => 'Cloud Provider',
            'telecom_provider' => 'Telecom Provider',
            'security_provider' => 'Security Provider',
            'maintenance_provider' => 'Maintenance Provider',
            'training_provider' => 'Training Provider',
            'other' => 'Other',
        ];
    }

    /**
     * Get available vendor categories.
     */
    public static function getVendorCategories()
    {
        return [
            'technology' => 'Technology',
            'networking' => 'Networking',
            'security' => 'Security',
            'cloud' => 'Cloud Services',
            'software' => 'Software',
            'hardware' => 'Hardware',
            'telecommunications' => 'Telecommunications',
            'professional_services' => 'Professional Services',
            'maintenance' => 'Maintenance & Support',
            'office_supplies' => 'Office Supplies',
            'facilities' => 'Facilities Management',
            'training' => 'Training & Education',
            'legal' => 'Legal Services',
            'financial' => 'Financial Services',
            'marketing' => 'Marketing Services',
            'other' => 'Other',
        ];
    }

    /**
     * Get available relationship statuses.
     */
    public static function getRelationshipStatuses()
    {
        return [
            'active' => 'Active',
            'inactive' => 'Inactive',
            'suspended' => 'Suspended',
            'terminated' => 'Terminated',
            'prospective' => 'Prospective',
        ];
    }

    /**
     * Get available payment terms.
     */
    public static function getPaymentTerms()
    {
        return [
            'net_15' => 'Net 15',
            'net_30' => 'Net 30',
            'net_45' => 'Net 45',
            'net_60' => 'Net 60',
            'net_90' => 'Net 90',
            'due_on_receipt' => 'Due on Receipt',
            '2_10_net_30' => '2/10 Net 30',
            'cash_on_delivery' => 'Cash on Delivery',
            'prepaid' => 'Prepaid',
            'custom' => 'Custom Terms',
        ];
    }

    /**
     * Get available payment methods.
     */
    public static function getPaymentMethods()
    {
        return [
            'check' => 'Check',
            'ach' => 'ACH Transfer',
            'wire_transfer' => 'Wire Transfer',
            'credit_card' => 'Credit Card',
            'online_payment' => 'Online Payment',
            'cash' => 'Cash',
            'other' => 'Other',
        ];
    }

    /**
     * Get available billing frequencies.
     */
    public static function getBillingFrequencies()
    {
        return [
            'one_time' => 'One Time',
            'weekly' => 'Weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'semi_annually' => 'Semi-Annually',
            'annually' => 'Annually',
            'as_needed' => 'As Needed',
        ];
    }
}