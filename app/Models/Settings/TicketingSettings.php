<?php

namespace App\Models\Settings;

class TicketingSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'ticketing';
    }

    public function getAttributes(): array
    {
        return [
            'ticket_prefix',
            'ticket_next_number',
            'ticket_from_name',
            'ticket_from_email',
            'ticket_email_parse',
            'ticket_client_general_notifications',
            'ticket_autoclose',
            'ticket_autoclose_hours',
            'ticket_new_ticket_notification_email',
            'ticket_categorization_rules',
            'ticket_priority_rules',
            'sla_definitions',
            'sla_escalation_policies',
            'auto_assignment_rules',
            'routing_logic',
            'approval_workflows',
            'time_tracking_enabled',
            'time_tracking_settings',
            'customer_satisfaction_enabled',
            'csat_settings',
            'ticket_templates',
            'ticket_automation_rules',
            'multichannel_settings',
            'queue_management_settings',
        ];
    }

    public function getTicketPrefix(): ?string
    {
        return $this->get('ticket_prefix', 'TKT');
    }

    public function setTicketPrefix(?string $prefix): self
    {
        $this->set('ticket_prefix', $prefix);
        return $this;
    }

    public function getTicketNextNumber(): int
    {
        return $this->get('ticket_next_number', 1);
    }

    public function setTicketNextNumber(int $number): self
    {
        $this->set('ticket_next_number', $number);
        return $this;
    }

    public function getNextTicketNumber(): int
    {
        $number = $this->getTicketNextNumber() ?: 1;
        $this->setTicketNextNumber($number + 1);
        $this->model->save();
        return $number;
    }

    public function getTicketFromName(): ?string
    {
        return $this->get('ticket_from_name');
    }

    public function setTicketFromName(?string $name): self
    {
        $this->set('ticket_from_name', $name);
        return $this;
    }

    public function getTicketFromEmail(): ?string
    {
        return $this->get('ticket_from_email');
    }

    public function setTicketFromEmail(?string $email): self
    {
        $this->set('ticket_from_email', $email);
        return $this;
    }

    public function isTicketEmailParseEnabled(): bool
    {
        return (bool) $this->get('ticket_email_parse', false);
    }

    public function setTicketEmailParseEnabled(bool $enabled): self
    {
        $this->set('ticket_email_parse', $enabled);
        return $this;
    }

    public function isTicketClientNotificationsEnabled(): bool
    {
        return (bool) $this->get('ticket_client_general_notifications', true);
    }

    public function setTicketClientNotificationsEnabled(bool $enabled): self
    {
        $this->set('ticket_client_general_notifications', $enabled);
        return $this;
    }

    public function isTicketAutocloseEnabled(): bool
    {
        return (bool) $this->get('ticket_autoclose', false);
    }

    public function setTicketAutocloseEnabled(bool $enabled): self
    {
        $this->set('ticket_autoclose', $enabled);
        return $this;
    }

    public function getTicketAutocloseHours(): int
    {
        return $this->get('ticket_autoclose_hours', 72);
    }

    public function setTicketAutocloseHours(int $hours): self
    {
        $this->set('ticket_autoclose_hours', $hours);
        return $this;
    }

    public function getNewTicketNotificationEmail(): ?string
    {
        return $this->get('ticket_new_ticket_notification_email');
    }

    public function setNewTicketNotificationEmail(?string $email): self
    {
        $this->set('ticket_new_ticket_notification_email', $email);
        return $this;
    }

    public function isTimeTrackingEnabled(): bool
    {
        return (bool) $this->get('time_tracking_enabled', false);
    }

    public function setTimeTrackingEnabled(bool $enabled): self
    {
        $this->set('time_tracking_enabled', $enabled);
        return $this;
    }

    public function isCustomerSatisfactionEnabled(): bool
    {
        return (bool) $this->get('customer_satisfaction_enabled', false);
    }

    public function setCustomerSatisfactionEnabled(bool $enabled): self
    {
        $this->set('customer_satisfaction_enabled', $enabled);
        return $this;
    }

    public function getCsatSettings(): ?array
    {
        return $this->get('csat_settings');
    }

    public function setCsatSettings(?array $settings): self
    {
        $this->set('csat_settings', $settings);
        return $this;
    }

    public function getSlaDefinitions(): ?array
    {
        return $this->get('sla_definitions');
    }

    public function setSlaDefinitions(?array $definitions): self
    {
        $this->set('sla_definitions', $definitions);
        return $this;
    }

    public function getTicketAutomationRules(): ?array
    {
        return $this->get('ticket_automation_rules');
    }

    public function setTicketAutomationRules(?array $rules): self
    {
        $this->set('ticket_automation_rules', $rules);
        return $this;
    }
}
