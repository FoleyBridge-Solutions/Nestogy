<?php

namespace App\Domains\Client\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

class ClientTrip extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'client_id',
        'trip_number',
        'title',
        'description',
        'purpose',
        'destination_address',
        'destination_city',
        'destination_state',
        'destination_country',
        'start_date',
        'end_date',
        'departure_time',
        'return_time',
        'status',
        'trip_type',
        'transportation_mode',
        'accommodation_details',
        'estimated_expenses',
        'actual_expenses',
        'currency',
        'mileage',
        'per_diem_amount',
        'billable_to_client',
        'reimbursable',
        'expense_breakdown',
        'receipts',
        'attendees',
        'notes',
        'weather_conditions',
        'traffic_conditions',
        'client_feedback',
        'internal_rating',
        'follow_up_required',
        'follow_up_notes',
        'approval_required',
        'approved_by',
        'approved_at',
        'submitted_for_reimbursement',
        'reimbursement_amount',
        'reimbursement_date',
        'metadata',
        'created_by',
        'completed_at',
        'cancelled_at',
        'cancellation_reason'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'departure_time' => 'datetime',
        'return_time' => 'datetime',
        'estimated_expenses' => 'decimal:2',
        'actual_expenses' => 'decimal:2',
        'per_diem_amount' => 'decimal:2',
        'reimbursement_amount' => 'decimal:2',
        'mileage' => 'decimal:2',
        'billable_to_client' => 'boolean',
        'reimbursable' => 'boolean',
        'follow_up_required' => 'boolean',
        'approval_required' => 'boolean',
        'submitted_for_reimbursement' => 'boolean',
        'approved_at' => 'datetime',
        'reimbursement_date' => 'date',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expense_breakdown' => 'array',
        'receipts' => 'array',
        'attendees' => 'array',
        'metadata' => 'array'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'departure_time',
        'return_time',
        'approved_at',
        'reimbursement_date',
        'completed_at',
        'cancelled_at'
    ];

    /**
     * Get the client that owns the trip
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the user who created the trip
     */
    public function creator()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    /**
     * Get the user who approved the trip
     */
    public function approver()
    {
        return $this->belongsTo(\App\Models\User::class, 'approved_by');
    }

    /**
     * Scope for upcoming trips
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now()->toDate())
                    ->where('status', '!=', 'cancelled');
    }

    /**
     * Scope for current trips (in progress)
     */
    public function scopeCurrent($query)
    {
        return $query->where('start_date', '<=', now()->toDate())
                    ->where('end_date', '>=', now()->toDate())
                    ->where('status', 'in_progress');
    }

    /**
     * Scope for completed trips
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for cancelled trips
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope for trips requiring approval
     */
    public function scopePendingApproval($query)
    {
        return $query->where('approval_required', true)
                    ->whereNull('approved_at');
    }

    /**
     * Scope for trips requiring follow-up
     */
    public function scopeFollowUpRequired($query)
    {
        return $query->where('follow_up_required', true)
                    ->where('status', 'completed');
    }

    /**
     * Scope for billable trips
     */
    public function scopeBillable($query)
    {
        return $query->where('billable_to_client', true);
    }

    /**
     * Scope for reimbursable trips
     */
    public function scopeReimbursable($query)
    {
        return $query->where('reimbursable', true);
    }

    /**
     * Get available statuses
     */
    public static function getStatuses()
    {
        return [
            'planned' => 'Planned',
            'approved' => 'Approved',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'postponed' => 'Postponed'
        ];
    }

    /**
     * Get trip types
     */
    public static function getTripTypes()
    {
        return [
            'client_visit' => 'Client Visit',
            'site_inspection' => 'Site Inspection',
            'meeting' => 'Business Meeting',
            'conference' => 'Conference/Event',
            'training' => 'Training',
            'support' => 'Technical Support',
            'installation' => 'Installation',
            'maintenance' => 'Maintenance',
            'emergency' => 'Emergency Call',
            'other' => 'Other'
        ];
    }

    /**
     * Get transportation modes
     */
    public static function getTransportationModes()
    {
        return [
            'car' => 'Personal Car',
            'company_car' => 'Company Car',
            'rental_car' => 'Rental Car',
            'airplane' => 'Airplane',
            'train' => 'Train',
            'bus' => 'Bus',
            'taxi' => 'Taxi/Rideshare',
            'public_transport' => 'Public Transport',
            'walking' => 'Walking',
            'other' => 'Other'
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
     * Check if trip is upcoming
     */
    public function isUpcoming()
    {
        return $this->start_date && $this->start_date->gte(now()->toDate()) && $this->status !== 'cancelled';
    }

    /**
     * Check if trip is current (in progress)
     */
    public function isCurrent()
    {
        return $this->start_date && $this->end_date &&
               $this->start_date->lte(now()->toDate()) && 
               $this->end_date->gte(now()->toDate()) &&
               $this->status === 'in_progress';
    }

    /**
     * Check if trip is completed
     */
    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    /**
     * Check if trip is cancelled
     */
    public function isCancelled()
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if trip requires approval
     */
    public function requiresApproval()
    {
        return $this->approval_required && !$this->approved_at;
    }

    /**
     * Check if trip is approved
     */
    public function isApproved()
    {
        return $this->approved_at !== null;
    }

    /**
     * Get trip duration in days
     */
    public function getDurationInDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Get formatted destination
     */
    public function getFormattedDestinationAttribute()
    {
        $parts = array_filter([
            $this->destination_city,
            $this->destination_state,
            $this->destination_country
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get full destination address
     */
    public function getFullDestinationAttribute()
    {
        $parts = array_filter([
            $this->destination_address,
            $this->destination_city,
            $this->destination_state,
            $this->destination_country
        ]);
        
        return implode(', ', $parts);
    }

    /**
     * Get formatted estimated expenses
     */
    public function getFormattedEstimatedExpensesAttribute()
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->estimated_expenses ?? 0, 2);
    }

    /**
     * Get formatted actual expenses
     */
    public function getFormattedActualExpensesAttribute()
    {
        $symbol = $this->getCurrencySymbol();
        return $symbol . number_format($this->actual_expenses ?? 0, 2);
    }

    /**
     * Get expense variance
     */
    public function getExpenseVarianceAttribute()
    {
        if (!$this->estimated_expenses || !$this->actual_expenses) {
            return null;
        }
        
        return $this->actual_expenses - $this->estimated_expenses;
    }

    /**
     * Get formatted expense variance
     */
    public function getFormattedExpenseVarianceAttribute()
    {
        $variance = $this->expense_variance;
        if ($variance === null) {
            return null;
        }
        
        $symbol = $this->getCurrencySymbol();
        $formatted = $symbol . number_format(abs($variance), 2);
        
        if ($variance > 0) {
            return '+' . $formatted . ' (Over Budget)';
        } elseif ($variance < 0) {
            return '-' . $formatted . ' (Under Budget)';
        } else {
            return 'On Budget';
        }
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
     * Get days until trip starts
     */
    public function getDaysUntilTripAttribute()
    {
        if (!$this->start_date) {
            return null;
        }
        
        return now()->diffInDays($this->start_date, false);
    }

    /**
     * Get time until trip in human readable format
     */
    public function getTimeUntilTripAttribute()
    {
        if (!$this->start_date) {
            return 'No start date';
        }
        
        $days = $this->days_until_trip;
        
        if ($days < 0) {
            return abs($days) . ' days ago';
        } elseif ($days == 0) {
            return 'Today';
        } elseif ($days == 1) {
            return 'Tomorrow';
        } else {
            return $days . ' days';
        }
    }

    /**
     * Generate trip number
     */
    public static function generateTripNumber($prefix = 'TRIP')
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        
        $lastTrip = static::where('trip_number', 'like', "{$prefix}-{$year}{$month}-%")
                         ->orderBy('trip_number', 'desc')
                         ->first();
        
        if ($lastTrip) {
            $lastNumber = (int) substr($lastTrip->trip_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . '-' . $year . $month . '-' . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Approve the trip
     */
    public function approve($approver = null)
    {
        $this->approved_by = $approver ?: auth()->id();
        $this->approved_at = now();
        $this->status = 'approved';
        $this->save();
        
        return true;
    }

    /**
     * Start the trip
     */
    public function start()
    {
        if (in_array($this->status, ['planned', 'approved'])) {
            $this->status = 'in_progress';
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Complete the trip
     */
    public function complete($completionData = [])
    {
        if ($this->status === 'in_progress') {
            $this->status = 'completed';
            $this->completed_at = now();
            
            if (isset($completionData['actual_expenses'])) {
                $this->actual_expenses = $completionData['actual_expenses'];
            }
            
            if (isset($completionData['client_feedback'])) {
                $this->client_feedback = $completionData['client_feedback'];
            }
            
            if (isset($completionData['internal_rating'])) {
                $this->internal_rating = $completionData['internal_rating'];
            }
            
            if (isset($completionData['follow_up_required'])) {
                $this->follow_up_required = $completionData['follow_up_required'];
            }
            
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Cancel the trip
     */
    public function cancel($reason = null)
    {
        if (!in_array($this->status, ['completed', 'cancelled'])) {
            $this->status = 'cancelled';
            $this->cancelled_at = now();
            $this->cancellation_reason = $reason;
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Submit for reimbursement
     */
    public function submitForReimbursement($amount = null)
    {
        if ($this->reimbursable && $this->isCompleted()) {
            $this->submitted_for_reimbursement = true;
            $this->reimbursement_amount = $amount ?: $this->actual_expenses;
            $this->save();
            
            return true;
        }
        
        return false;
    }

    /**
     * Calculate total expense breakdown
     */
    public function calculateTotalExpenses()
    {
        if (!$this->expense_breakdown || !is_array($this->expense_breakdown)) {
            return 0;
        }
        
        $total = 0;
        foreach ($this->expense_breakdown as $expense) {
            $total += $expense['amount'] ?? 0;
        }
        
        return $total;
    }
}