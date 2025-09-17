<?php

namespace App\Domains\PhysicalMail\Traits;

use App\Domains\PhysicalMail\Models\PhysicalMailOrder;
use App\Domains\PhysicalMail\Services\PhysicalMailService;

trait HasPhysicalMail
{
    /**
     * Get all physical mail orders for this model
     */
    public function physicalMailOrders()
    {
        return $this->morphMany(PhysicalMailOrder::class, 'mailable');
    }

    /**
     * Send this model by physical mail
     */
    public function sendByMail(array $options = []): PhysicalMailOrder
    {
        $service = app(PhysicalMailService::class);
        
        $data = array_merge([
            'to' => $this->getMailingAddress(),
            'from' => config('physical_mail.defaults.from_address'),
            'template_id' => $this->getMailTemplate(),
            'merge_variables' => $this->getMailMergeVariables(),
            'client_id' => $this->getMailClientId(),
        ], $options);
        
        return $service->send($this->getMailType(), $data);
    }

    /**
     * Get the mailing address for this model
     */
    abstract protected function getMailingAddress(): array;

    /**
     * Get the mail template ID for this model
     */
    abstract protected function getMailTemplate(): ?string;

    /**
     * Get merge variables for mail template
     */
    abstract protected function getMailMergeVariables(): array;

    /**
     * Get the mail type (Letter, Postcard, etc.)
     */
    abstract protected function getMailType(): string;

    /**
     * Get the client ID for this mailing
     */
    protected function getMailClientId(): ?string
    {
        return null; // Override in model if needed
    }
}