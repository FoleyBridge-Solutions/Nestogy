<?php

namespace App\Models\Settings;

class ComplianceSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'compliance';
    }

    public function getAttributes(): array
    {
        return [
            'soc2_compliance_enabled',
            'soc2_settings',
            'hipaa_compliance_enabled',
            'hipaa_settings',
            'pci_compliance_enabled',
            'pci_settings',
            'gdpr_compliance_enabled',
            'gdpr_settings',
            'industry_compliance_settings',
            'data_retention_policies',
            'data_destruction_policies',
            'risk_assessment_settings',
            'vendor_compliance_settings',
            'incident_response_settings',
            'backup_policies',
            'backup_schedules',
            'recovery_time_objective',
            'recovery_point_objective',
            'disaster_recovery_procedures',
            'data_replication_settings',
            'business_continuity_settings',
            'testing_validation_schedules',
            'cloud_backup_settings',
            'ransomware_protection_settings',
            'recovery_documentation_settings',
        ];
    }

    public function isSoc2Enabled(): bool
    {
        return (bool) $this->get('soc2_compliance_enabled', false);
    }

    public function setSoc2Enabled(bool $enabled): self
    {
        $this->set('soc2_compliance_enabled', $enabled);
        return $this;
    }

    public function getSoc2Settings(): ?array
    {
        return $this->get('soc2_settings');
    }

    public function setSoc2Settings(?array $settings): self
    {
        $this->set('soc2_settings', $settings);
        return $this;
    }

    public function isHipaaEnabled(): bool
    {
        return (bool) $this->get('hipaa_compliance_enabled', false);
    }

    public function setHipaaEnabled(bool $enabled): self
    {
        $this->set('hipaa_compliance_enabled', $enabled);
        return $this;
    }

    public function getHipaaSettings(): ?array
    {
        return $this->get('hipaa_settings');
    }

    public function setHipaaSettings(?array $settings): self
    {
        $this->set('hipaa_settings', $settings);
        return $this;
    }

    public function isPciEnabled(): bool
    {
        return (bool) $this->get('pci_compliance_enabled', false);
    }

    public function setPciEnabled(bool $enabled): self
    {
        $this->set('pci_compliance_enabled', $enabled);
        return $this;
    }

    public function getPciSettings(): ?array
    {
        return $this->get('pci_settings');
    }

    public function setPciSettings(?array $settings): self
    {
        $this->set('pci_settings', $settings);
        return $this;
    }

    public function isGdprEnabled(): bool
    {
        return (bool) $this->get('gdpr_compliance_enabled', false);
    }

    public function setGdprEnabled(bool $enabled): self
    {
        $this->set('gdpr_compliance_enabled', $enabled);
        return $this;
    }

    public function getGdprSettings(): ?array
    {
        return $this->get('gdpr_settings');
    }

    public function setGdprSettings(?array $settings): self
    {
        $this->set('gdpr_settings', $settings);
        return $this;
    }

    public function getComplianceStatus(): array
    {
        return [
            'soc2' => $this->isSoc2Enabled(),
            'hipaa' => $this->isHipaaEnabled(),
            'pci' => $this->isPciEnabled(),
            'gdpr' => $this->isGdprEnabled(),
        ];
    }

    public function getBackupPolicies(): ?array
    {
        return $this->get('backup_policies');
    }

    public function setBackupPolicies(?array $policies): self
    {
        $this->set('backup_policies', $policies);
        return $this;
    }

    public function getRecoveryTimeObjective(): ?int
    {
        return $this->get('recovery_time_objective');
    }

    public function setRecoveryTimeObjective(?int $rto): self
    {
        $this->set('recovery_time_objective', $rto);
        return $this;
    }

    public function getRecoveryPointObjective(): ?int
    {
        return $this->get('recovery_point_objective');
    }

    public function setRecoveryPointObjective(?int $rpo): self
    {
        $this->set('recovery_point_objective', $rpo);
        return $this;
    }

    public function getDataRetentionPolicies(): ?array
    {
        return $this->get('data_retention_policies');
    }

    public function setDataRetentionPolicies(?array $policies): self
    {
        $this->set('data_retention_policies', $policies);
        return $this;
    }
}
