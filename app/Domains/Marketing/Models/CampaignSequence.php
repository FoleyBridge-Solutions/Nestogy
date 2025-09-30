<?php

namespace App\Domains\Marketing\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CampaignSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'campaign_id',
        'name',
        'step_number',
        'delay_days',
        'delay_hours',
        'subject_line',
        'email_template',
        'email_text',
        'send_conditions',
        'skip_conditions',
        'is_active',
        'send_time',
        'send_days',
    ];

    protected $casts = [
        'campaign_id' => 'integer',
        'step_number' => 'integer',
        'delay_days' => 'integer',
        'delay_hours' => 'integer',
        'send_conditions' => 'array',
        'skip_conditions' => 'array',
        'is_active' => 'boolean',
        'send_time' => 'time',
        'send_days' => 'array',
    ];

    /**
     * Get the campaign this sequence belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(MarketingCampaign::class, 'campaign_id');
    }

    /**
     * Get total delay in hours.
     */
    public function getTotalDelayHoursAttribute(): int
    {
        return ($this->delay_days * 24) + $this->delay_hours;
    }

    /**
     * Get delay description.
     */
    public function getDelayDescriptionAttribute(): string
    {
        if ($this->delay_days === 0 && $this->delay_hours === 0) {
            return 'Immediate';
        }

        $parts = [];

        if ($this->delay_days > 0) {
            $parts[] = $this->delay_days.' day'.($this->delay_days > 1 ? 's' : '');
        }

        if ($this->delay_hours > 0) {
            $parts[] = $this->delay_hours.' hour'.($this->delay_hours > 1 ? 's' : '');
        }

        return implode(' and ', $parts).' after previous step';
    }

    /**
     * Get formatted send time.
     */
    public function getFormattedSendTimeAttribute(): string
    {
        return $this->send_time ? $this->send_time->format('g:i A') : '9:00 AM';
    }

    /**
     * Get send days description.
     */
    public function getSendDaysDescriptionAttribute(): string
    {
        if (! $this->send_days || empty($this->send_days)) {
            return 'Any day';
        }

        $dayNames = [
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
            7 => 'Sunday',
        ];

        $selectedDays = array_map(function ($day) use ($dayNames) {
            return $dayNames[$day] ?? $day;
        }, $this->send_days);

        return implode(', ', $selectedDays);
    }

    /**
     * Check if this step can be sent on a given day.
     */
    public function canSendOnDay(int $dayOfWeek): bool
    {
        if (! $this->send_days || empty($this->send_days)) {
            return true;
        }

        return in_array($dayOfWeek, $this->send_days);
    }

    /**
     * Check if send conditions are met for a given recipient.
     */
    public function checkSendConditions(array $recipientData): bool
    {
        if (! $this->send_conditions || empty($this->send_conditions)) {
            return true;
        }

        // Implementation would depend on specific condition logic
        // For now, return true
        return true;
    }

    /**
     * Check if skip conditions are met for a given recipient.
     */
    public function checkSkipConditions(array $recipientData): bool
    {
        if (! $this->skip_conditions || empty($this->skip_conditions)) {
            return false;
        }

        // Implementation would depend on specific condition logic
        // For now, return false
        return false;
    }

    /**
     * Get the previous sequence step.
     */
    public function getPreviousStep(): ?self
    {
        return self::where('campaign_id', $this->campaign_id)
            ->where('step_number', '<', $this->step_number)
            ->orderBy('step_number', 'desc')
            ->first();
    }

    /**
     * Get the next sequence step.
     */
    public function getNextStep(): ?self
    {
        return self::where('campaign_id', $this->campaign_id)
            ->where('step_number', '>', $this->step_number)
            ->orderBy('step_number', 'asc')
            ->first();
    }

    /**
     * Check if this is the first step.
     */
    public function isFirstStep(): bool
    {
        return $this->step_number === 1;
    }

    /**
     * Check if this is the last step.
     */
    public function isLastStep(): bool
    {
        $maxStep = self::where('campaign_id', $this->campaign_id)
            ->where('is_active', true)
            ->max('step_number');

        return $this->step_number === $maxStep;
    }

    /**
     * Scope to get active sequences.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to order by step number.
     */
    public function scopeOrderedByStep($query)
    {
        return $query->orderBy('step_number');
    }

    /**
     * Clone this sequence for another campaign.
     */
    public function cloneFor(MarketingCampaign $campaign): self
    {
        $clone = $this->replicate();
        $clone->campaign_id = $campaign->id;
        $clone->save();

        return $clone;
    }

    /**
     * Get email template with placeholder replacements.
     */
    public function getProcessedEmailTemplate(array $variables = []): string
    {
        $template = $this->email_template;

        foreach ($variables as $key => $value) {
            $template = str_replace('{{'.$key.'}}', $value, $template);
        }

        return $template;
    }

    /**
     * Get subject line with placeholder replacements.
     */
    public function getProcessedSubjectLine(array $variables = []): string
    {
        $subject = $this->subject_line;

        foreach ($variables as $key => $value) {
            $subject = str_replace('{{'.$key.'}}', $value, $subject);
        }

        return $subject;
    }
}
