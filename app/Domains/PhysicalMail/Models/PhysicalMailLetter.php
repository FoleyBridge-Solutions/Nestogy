<?php

namespace App\Domains\PhysicalMail\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class PhysicalMailLetter extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'physical_mail_letters';

    protected $fillable = [
        'to_contact_id',
        'from_contact_id',
        'template_id',
        'content',
        'color',
        'double_sided',
        'address_placement',
        'size',
        'perforated_page',
        'return_envelope_id',
        'extra_service',
        'merge_variables',
        'idempotency_key',
    ];

    protected $casts = [
        'color' => 'boolean',
        'double_sided' => 'boolean',
        'merge_variables' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->idempotency_key)) {
                $model->idempotency_key = (string) Str::uuid();
            }
        });
    }

    /**
     * Get the order for this letter
     */
    public function order(): MorphOne
    {
        return $this->morphOne(PhysicalMailOrder::class, 'mailable');
    }

    /**
     * Get the recipient contact
     */
    public function toContact(): BelongsTo
    {
        return $this->belongsTo(PhysicalMailContact::class, 'to_contact_id');
    }

    /**
     * Get the sender contact
     */
    public function fromContact(): BelongsTo
    {
        return $this->belongsTo(PhysicalMailContact::class, 'from_contact_id');
    }

    /**
     * Get the template
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(PhysicalMailTemplate::class, 'template_id');
    }

    /**
     * Get the return envelope
     */
    public function returnEnvelope(): BelongsTo
    {
        return $this->belongsTo(PhysicalMailReturnEnvelope::class, 'return_envelope_id');
    }

    /**
     * Check if letter has color
     */
    public function isColor(): bool
    {
        return $this->color;
    }

    /**
     * Check if letter is double-sided
     */
    public function isDoubleSided(): bool
    {
        return $this->double_sided;
    }

    /**
     * Check if letter has extra service
     */
    public function hasExtraService(): bool
    {
        return ! empty($this->extra_service);
    }

    /**
     * Get the mail type for polymorphic relationships
     */
    public function getMailTypeAttribute(): string
    {
        return 'letter';
    }

    /**
     * Convert to PostGrid API format
     */
    public function toPostGridArray(): array
    {
        $data = [
            'to' => $this->toContact->postgrid_id ?? $this->toContact->toPostGridArray(),
            'from' => $this->fromContact->postgrid_id ?? $this->fromContact->toPostGridArray(),
            'color' => $this->color,
            'doubleSided' => $this->double_sided,
            'addressPlacement' => $this->address_placement,
            'size' => $this->size,
        ];

        // Add content (template, HTML, or PDF)
        if ($this->template_id) {
            $data['template'] = $this->template->postgrid_id;
        } elseif ($this->content) {
            // Determine if content is HTML or PDF URL
            if (filter_var($this->content, FILTER_VALIDATE_URL)) {
                $data['pdf'] = $this->content;
            } else {
                $data['html'] = $this->content;
            }
        }

        // Add optional fields
        if ($this->perforated_page) {
            $data['perforatedPage'] = $this->perforated_page;
        }

        if ($this->extra_service) {
            $data['extraService'] = $this->extra_service;
        }

        if (! empty($this->merge_variables)) {
            $data['mergeVariables'] = $this->merge_variables;
        }

        if ($this->return_envelope_id) {
            $data['returnEnvelope'] = $this->returnEnvelope->postgrid_id;
        }

        return $data;
    }
}
