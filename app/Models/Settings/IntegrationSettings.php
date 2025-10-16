<?php

namespace App\Models\Settings;

class IntegrationSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'integration';
    }

    public function getAttributes(): array
    {
        return [
            'connectwise_automate_settings',
            'datto_rmm_settings',
            'ninja_rmm_settings',
            'kaseya_vsa_settings',
            'auvik_settings',
            'prtg_settings',
            'solarwinds_settings',
            'monitoring_alert_thresholds',
            'escalation_rules',
            'asset_discovery_settings',
            'patch_management_settings',
            'remote_access_settings',
            'auto_create_tickets_from_alerts',
            'alert_to_ticket_mapping',
            'api_key_management_settings',
            'api_rate_limiting_settings',
            'api_throttling_settings',
            'webhook_configuration_settings',
            'third_party_integration_settings',
            'data_mapping_settings',
            'sync_scheduling_settings',
            'integration_monitoring_settings',
            'custom_connector_settings',
            'marketplace_integration_settings',
            'legacy_system_bridge_settings',
        ];
    }

    public function getConnectwiseAutomateSettings(): ?array
    {
        return $this->get('connectwise_automate_settings');
    }

    public function setConnectwiseAutomateSettings(?array $settings): self
    {
        $this->set('connectwise_automate_settings', $settings);
        return $this;
    }

    public function getDattoRmmSettings(): ?array
    {
        return $this->get('datto_rmm_settings');
    }

    public function setDattoRmmSettings(?array $settings): self
    {
        $this->set('datto_rmm_settings', $settings);
        return $this;
    }

    public function getNinjaRmmSettings(): ?array
    {
        return $this->get('ninja_rmm_settings');
    }

    public function setNinjaRmmSettings(?array $settings): self
    {
        $this->set('ninja_rmm_settings', $settings);
        return $this;
    }

    public function getKasyaVsaSettings(): ?array
    {
        return $this->get('kaseya_vsa_settings');
    }

    public function setKasyaVsaSettings(?array $settings): self
    {
        $this->set('kaseya_vsa_settings', $settings);
        return $this;
    }

    public function getAuvikSettings(): ?array
    {
        return $this->get('auvik_settings');
    }

    public function setAuvikSettings(?array $settings): self
    {
        $this->set('auvik_settings', $settings);
        return $this;
    }

    public function getPrtgSettings(): ?array
    {
        return $this->get('prtg_settings');
    }

    public function setPrtgSettings(?array $settings): self
    {
        $this->set('prtg_settings', $settings);
        return $this;
    }

    public function getSolarwindsSettings(): ?array
    {
        return $this->get('solarwinds_settings');
    }

    public function setSolarwindsSettings(?array $settings): self
    {
        $this->set('solarwinds_settings', $settings);
        return $this;
    }

    public function hasRmmIntegration(): bool
    {
        return ! empty($this->getConnectwiseAutomateSettings())
            || ! empty($this->getDattoRmmSettings())
            || ! empty($this->getNinjaRmmSettings())
            || ! empty($this->getKasyaVsaSettings());
    }

    public function hasMonitoringIntegration(): bool
    {
        return ! empty($this->getAuvikSettings())
            || ! empty($this->getPrtgSettings())
            || ! empty($this->getSolarwindsSettings());
    }

    public function isAutoCreateTicketsFromAlertsEnabled(): bool
    {
        return (bool) $this->get('auto_create_tickets_from_alerts', false);
    }

    public function setAutoCreateTicketsFromAlertsEnabled(bool $enabled): self
    {
        $this->set('auto_create_tickets_from_alerts', $enabled);
        return $this;
    }

    public function getAlertToTicketMapping(): ?array
    {
        return $this->get('alert_to_ticket_mapping');
    }

    public function setAlertToTicketMapping(?array $mapping): self
    {
        $this->set('alert_to_ticket_mapping', $mapping);
        return $this;
    }
}
