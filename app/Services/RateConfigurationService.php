<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Company;
use App\Domains\Contract\Models\Contract;
use App\Domains\Ticket\Models\Ticket;
use Carbon\Carbon;

/**
 * Rate Configuration Service
 * 
 * Centralized service for managing hourly rates across the platform.
 * Handles rate hierarchy: Contract > Client > Company
 */
class RateConfigurationService
{
    /**
     * Rate types
     */
    const RATE_STANDARD = 'standard';
    const RATE_AFTER_HOURS = 'after_hours';
    const RATE_EMERGENCY = 'emergency';
    const RATE_WEEKEND = 'weekend';
    const RATE_HOLIDAY = 'holiday';

    /**
     * Rate calculation methods
     */
    const METHOD_FIXED = 'fixed_rates';
    const METHOD_MULTIPLIERS = 'multipliers';

    /**
     * Time rounding methods
     */
    const ROUNDING_NONE = 'none';
    const ROUNDING_UP = 'up';
    const ROUNDING_DOWN = 'down';
    const ROUNDING_NEAREST = 'nearest';

    /**
     * Get hourly rate for a ticket using the rate hierarchy.
     * Priority: Contract rates > Client rates > Company rates
     */
    public function getTicketHourlyRate(Ticket $ticket, string $rateType = self::RATE_STANDARD): float
    {
        // Check contract rates first (highest priority)
        if ($ticket->client && $ticket->client->activeContract) {
            $contractRate = $this->getContractHourlyRate($ticket->client->activeContract, $rateType);
            if ($contractRate !== null) {
                return $contractRate;
            }
        }

        // Check client rates (medium priority)
        if ($ticket->client) {
            return $this->getClientHourlyRate($ticket->client, $rateType);
        }

        // Fall back to company rates (lowest priority)
        return $this->getCompanyHourlyRate($ticket->client->company, $rateType);
    }

    /**
     * Get hourly rate from contract pricing structure.
     */
    public function getContractHourlyRate(Contract $contract, string $rateType = self::RATE_STANDARD): ?float
    {
        if (!$contract->pricing_structure || !isset($contract->pricing_structure['hourly_rates'])) {
            return null;
        }

        $hourlyRates = $contract->pricing_structure['hourly_rates'];
        
        return $hourlyRates[$rateType] ?? $hourlyRates[self::RATE_STANDARD] ?? null;
    }

    /**
     * Get hourly rate from client configuration.
     */
    public function getClientHourlyRate(Client $client, string $rateType = self::RATE_STANDARD): float
    {
        return $client->getHourlyRate($rateType);
    }

    /**
     * Get hourly rate from company configuration.
     */
    public function getCompanyHourlyRate(Company $company, string $rateType = self::RATE_STANDARD): float
    {
        return $company->getHourlyRate($rateType);
    }

    /**
     * Determine if time should be billable based on contract terms.
     */
    public function isTimeBillable(Ticket $ticket, Carbon $startTime, Carbon $endTime): bool
    {
        // Check contract included hours first
        if ($ticket->client && $ticket->client->activeContract) {
            $contract = $ticket->client->activeContract;
            
            if ($this->isWithinContractIncludedHours($contract, $startTime, $endTime)) {
                return false; // Within included hours, not billable
            }
        }

        // Check ticket type overrides
        if (in_array($ticket->type, ['warranty', 'internal', 'training'])) {
            return false;
        }

        // Default to billable
        return true;
    }

    /**
     * Check if time falls within contract included hours.
     */
    protected function isWithinContractIncludedHours(Contract $contract, Carbon $startTime, Carbon $endTime): bool
    {
        if (!$contract->pricing_structure || !isset($contract->pricing_structure['included_hours'])) {
            return false;
        }

        $includedHours = $contract->pricing_structure['included_hours'];
        $monthStart = $startTime->copy()->startOfMonth();
        $monthEnd = $startTime->copy()->endOfMonth();

        // Calculate hours used this month (this would need to query TicketTimeEntry)
        $usedHours = $this->getContractHoursUsed($contract, $monthStart, $monthEnd);
        $requestedHours = $endTime->diffInHours($startTime);

        return ($usedHours + $requestedHours) <= $includedHours;
    }

    /**
     * Get hours used for a contract in a given period.
     * This would query the TicketTimeEntry model.
     */
    protected function getContractHoursUsed(Contract $contract, Carbon $startDate, Carbon $endDate): float
    {
        // This should be implemented to query actual time entries
        // For now, return 0 as placeholder
        return 0.0;
    }

    /**
     * Round time according to client/company settings.
     */
    public function roundTime(float $hours, Client $client): float
    {
        return $client->roundTime($hours);
    }

    /**
     * Determine rate type based on time of work.
     */
    public function determineRateType(Carbon $workTime, bool $isEmergency = false): string
    {
        if ($isEmergency) {
            return self::RATE_EMERGENCY;
        }

        // Check if it's a holiday
        if ($this->isHoliday($workTime)) {
            return self::RATE_HOLIDAY;
        }

        // Check if it's a weekend
        if ($workTime->isWeekend()) {
            return self::RATE_WEEKEND;
        }

        // Check if it's after hours (before 8 AM or after 6 PM)
        $hour = $workTime->hour;
        if ($hour < 8 || $hour >= 18) {
            return self::RATE_AFTER_HOURS;
        }

        return self::RATE_STANDARD;
    }

    /**
     * Check if a date is a company holiday.
     * This could be enhanced to check against a holidays table.
     */
    protected function isHoliday(Carbon $date): bool
    {
        // Basic US holidays check
        $holidays = [
            $date->year . '-01-01', // New Year's Day
            $date->year . '-07-04', // Independence Day
            $date->year . '-12-25', // Christmas
        ];

        return in_array($date->format('Y-m-d'), $holidays);
    }

    /**
     * Get all available rate types.
     */
    public static function getRateTypes(): array
    {
        return [
            self::RATE_STANDARD => 'Standard',
            self::RATE_AFTER_HOURS => 'After Hours',
            self::RATE_EMERGENCY => 'Emergency',
            self::RATE_WEEKEND => 'Weekend',
            self::RATE_HOLIDAY => 'Holiday',
        ];
    }

    /**
     * Get rate calculation methods.
     */
    public static function getRateCalculationMethods(): array
    {
        return [
            self::METHOD_FIXED => 'Fixed Rates',
            self::METHOD_MULTIPLIERS => 'Base Rate + Multipliers',
        ];
    }

    /**
     * Get time rounding methods.
     */
    public static function getTimeRoundingMethods(): array
    {
        return [
            self::ROUNDING_NONE => 'No Rounding',
            self::ROUNDING_UP => 'Round Up',
            self::ROUNDING_DOWN => 'Round Down',
            self::ROUNDING_NEAREST => 'Round to Nearest',
        ];
    }

    /**
     * Validate rate configuration array.
     */
    public function validateRateConfiguration(array $config): array
    {
        $errors = [];

        // Validate rate calculation method
        if (isset($config['rate_calculation_method']) && 
            !in_array($config['rate_calculation_method'], [self::METHOD_FIXED, self::METHOD_MULTIPLIERS])) {
            $errors[] = 'Invalid rate calculation method';
        }

        // Validate rates are positive numbers
        $rateFields = [
            'default_standard_rate', 'default_after_hours_rate', 'default_emergency_rate',
            'default_weekend_rate', 'default_holiday_rate'
        ];

        foreach ($rateFields as $field) {
            if (isset($config[$field]) && (!is_numeric($config[$field]) || $config[$field] < 0)) {
                $errors[] = "Invalid {$field}: must be a positive number";
            }
        }

        // Validate multipliers
        $multiplierFields = [
            'after_hours_multiplier', 'emergency_multiplier', 'weekend_multiplier', 'holiday_multiplier'
        ];

        foreach ($multiplierFields as $field) {
            if (isset($config[$field]) && (!is_numeric($config[$field]) || $config[$field] < 0)) {
                $errors[] = "Invalid {$field}: must be a positive number";
            }
        }

        // Validate minimum billing increment
        if (isset($config['minimum_billing_increment']) && 
            (!is_numeric($config['minimum_billing_increment']) || $config['minimum_billing_increment'] <= 0)) {
            $errors[] = 'Minimum billing increment must be a positive number';
        }

        return $errors;
    }
}