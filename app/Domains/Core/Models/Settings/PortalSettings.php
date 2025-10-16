<?php

namespace App\Domains\Core\Models\Settings;

class PortalSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'portal';
    }

    public function getAttributes(): array
    {
        return [
            'client_portal_enable',
            'portal_branding_settings',
            'portal_customization_settings',
            'portal_access_controls',
            'portal_feature_toggles',
            'portal_self_service_tickets',
            'portal_knowledge_base_access',
            'portal_invoice_access',
            'portal_payment_processing',
            'portal_asset_visibility',
            'portal_sso_settings',
            'portal_mobile_settings',
            'portal_dashboard_settings',
        ];
    }

    public function isEnabled(): bool
    {
        return (bool) $this->get('client_portal_enable', false);
    }

    public function setEnabled(bool $enabled): self
    {
        $this->set('client_portal_enable', $enabled);
        return $this;
    }

    public function getBrandingSettings(): ?array
    {
        return $this->get('portal_branding_settings');
    }

    public function setBrandingSettings(?array $settings): self
    {
        $this->set('portal_branding_settings', $settings);
        return $this;
    }

    public function getCustomizationSettings(): ?array
    {
        return $this->get('portal_customization_settings');
    }

    public function setCustomizationSettings(?array $settings): self
    {
        $this->set('portal_customization_settings', $settings);
        return $this;
    }

    public function isSelfServiceTicketsEnabled(): bool
    {
        return (bool) $this->get('portal_self_service_tickets', false);
    }

    public function setSelfServiceTicketsEnabled(bool $enabled): self
    {
        $this->set('portal_self_service_tickets', $enabled);
        return $this;
    }

    public function isKnowledgeBaseAccessEnabled(): bool
    {
        return (bool) $this->get('portal_knowledge_base_access', false);
    }

    public function setKnowledgeBaseAccessEnabled(bool $enabled): self
    {
        $this->set('portal_knowledge_base_access', $enabled);
        return $this;
    }

    public function isInvoiceAccessEnabled(): bool
    {
        return (bool) $this->get('portal_invoice_access', false);
    }

    public function setInvoiceAccessEnabled(bool $enabled): self
    {
        $this->set('portal_invoice_access', $enabled);
        return $this;
    }

    public function isPaymentProcessingEnabled(): bool
    {
        return (bool) $this->get('portal_payment_processing', false);
    }

    public function setPaymentProcessingEnabled(bool $enabled): self
    {
        $this->set('portal_payment_processing', $enabled);
        return $this;
    }

    public function isAssetVisibilityEnabled(): bool
    {
        return (bool) $this->get('portal_asset_visibility', false);
    }

    public function setAssetVisibilityEnabled(bool $enabled): self
    {
        $this->set('portal_asset_visibility', $enabled);
        return $this;
    }

    public function getSsoSettings(): ?array
    {
        return $this->get('portal_sso_settings');
    }

    public function setSsoSettings(?array $settings): self
    {
        $this->set('portal_sso_settings', $settings);
        return $this;
    }

    public function getStatus(): array
    {
        return [
            'enabled' => $this->isEnabled(),
            'self_service_tickets' => $this->isSelfServiceTicketsEnabled(),
            'knowledge_base_access' => $this->isKnowledgeBaseAccessEnabled(),
            'invoice_access' => $this->isInvoiceAccessEnabled(),
            'payment_processing' => $this->isPaymentProcessingEnabled(),
            'asset_visibility' => $this->isAssetVisibilityEnabled(),
        ];
    }
}
