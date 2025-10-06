<?php

namespace App\Domains\Product\Services;

use App\Models\Client;
use App\Models\Company;
use Carbon\Carbon;

class RateConfigurationService
{
    const RATE_STANDARD = 100.00;
    const RATE_AFTER_HOURS = 150.00;
    const RATE_EMERGENCY = 200.00;
    const RATE_WEEKEND = 175.00;
    const RATE_HOLIDAY = 250.00;

    public function getStandardRate(Client $client = null): float
    {
        return $client?->hourly_rate ?? self::RATE_STANDARD;
    }

    public function getAfterHoursRate(Client $client = null): float
    {
        return $client?->after_hours_rate ?? self::RATE_AFTER_HOURS;
    }

    public function getEmergencyRate(Client $client = null): float
    {
        return $client?->emergency_rate ?? self::RATE_EMERGENCY;
    }

    public function getWeekendRate(Client $client = null): float
    {
        return $client?->weekend_rate ?? self::RATE_WEEKEND;
    }

    public function getHolidayRate(Client $client = null): float
    {
        return $client?->holiday_rate ?? self::RATE_HOLIDAY;
    }

    public function getRateForDateTime(Carbon $dateTime, Client $client = null): float
    {
        if ($this->isHoliday($dateTime)) {
            return $this->getHolidayRate($client);
        }

        if ($dateTime->isWeekend()) {
            return $this->getWeekendRate($client);
        }

        if ($this->isAfterHours($dateTime)) {
            return $this->getAfterHoursRate($client);
        }

        return $this->getStandardRate($client);
    }

    protected function isAfterHours(Carbon $dateTime): bool
    {
        $hour = $dateTime->hour;
        return $hour < 8 || $hour >= 18;
    }

    protected function isHoliday(Carbon $dateTime): bool
    {
        $holidays = [
            '01-01',
            '07-04',
            '12-25',
        ];

        $dateString = $dateTime->format('m-d');
        return in_array($dateString, $holidays);
    }

    public function isTimeBillable($ticket, Carbon $startTime = null, Carbon $endTime = null): bool
    {
        if (method_exists($ticket, 'isBillable')) {
            return $ticket->isBillable();
        }
        
        return true;
    }

    public function determineRateType(Carbon $time, bool $isEmergency = false): string
    {
        if ($isEmergency) {
            return 'emergency';
        }

        if ($this->isHoliday($time)) {
            return 'holiday';
        }

        if ($time->isWeekend()) {
            return 'weekend';
        }

        if ($this->isAfterHours($time)) {
            return 'after_hours';
        }

        return 'standard';
    }

    public function getTicketHourlyRate($ticket, string $rateType): float
    {
        $client = $ticket->client ?? null;
        
        return match($rateType) {
            'emergency' => $this->getEmergencyRate($client),
            'holiday' => $this->getHolidayRate($client),
            'weekend' => $this->getWeekendRate($client),
            'after_hours' => $this->getAfterHoursRate($client),
            default => $this->getStandardRate($client),
        };
    }
}
