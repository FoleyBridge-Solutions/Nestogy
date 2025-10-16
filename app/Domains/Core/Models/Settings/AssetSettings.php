<?php

namespace App\Domains\Core\Models\Settings;

class AssetSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'asset';
    }

    public function getAttributes(): array
    {
        return [
            'asset_discovery_rules',
            'asset_lifecycle_settings',
            'software_license_settings',
            'hardware_warranty_settings',
            'procurement_settings',
            'vendor_management_settings',
            'asset_depreciation_settings',
            'asset_tracking_settings',
            'barcode_scanning_enabled',
            'barcode_settings',
            'mobile_asset_management_enabled',
            'asset_relationship_settings',
            'asset_compliance_settings',
        ];
    }

    public function getAssetDiscoveryRules(): ?array
    {
        return $this->get('asset_discovery_rules');
    }

    public function setAssetDiscoveryRules(?array $rules): self
    {
        $this->set('asset_discovery_rules', $rules);
        return $this;
    }

    public function getAssetLifecycleSettings(): ?array
    {
        return $this->get('asset_lifecycle_settings');
    }

    public function setAssetLifecycleSettings(?array $settings): self
    {
        $this->set('asset_lifecycle_settings', $settings);
        return $this;
    }

    public function isBarcodeScannlingEnabled(): bool
    {
        return (bool) $this->get('barcode_scanning_enabled', false);
    }

    public function setBarcodeScanningEnabled(bool $enabled): self
    {
        $this->set('barcode_scanning_enabled', $enabled);
        return $this;
    }

    public function getBarcodeSettings(): ?array
    {
        return $this->get('barcode_settings');
    }

    public function setBarcodeSettings(?array $settings): self
    {
        $this->set('barcode_settings', $settings);
        return $this;
    }

    public function isMobileAssetManagementEnabled(): bool
    {
        return (bool) $this->get('mobile_asset_management_enabled', false);
    }

    public function setMobileAssetManagementEnabled(bool $enabled): self
    {
        $this->set('mobile_asset_management_enabled', $enabled);
        return $this;
    }

    public function getSoftwareLicenseSettings(): ?array
    {
        return $this->get('software_license_settings');
    }

    public function setSoftwareLicenseSettings(?array $settings): self
    {
        $this->set('software_license_settings', $settings);
        return $this;
    }

    public function getHardwareWarrantySettings(): ?array
    {
        return $this->get('hardware_warranty_settings');
    }

    public function setHardwareWarrantySettings(?array $settings): self
    {
        $this->set('hardware_warranty_settings', $settings);
        return $this;
    }
}
