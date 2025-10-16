<?php

namespace App\Models\Settings;

class EmailSettings extends SettingCategory
{
    public function getCategory(): string
    {
        return 'email';
    }

    public function getAttributes(): array
    {
        return [
            'smtp_host',
            'smtp_port',
            'smtp_encryption',
            'smtp_auth_method',
            'smtp_username',
            'smtp_password',
            'smtp_auth_required',
            'smtp_use_tls',
            'smtp_timeout',
            'mail_from_email',
            'mail_from_name',
            'email_retry_attempts',
            'email_templates',
            'email_signatures',
            'email_tracking_enabled',
            'imap_host',
            'imap_port',
            'imap_encryption',
            'imap_auth_method',
            'imap_username',
            'imap_password',
            'sms_settings',
            'voice_settings',
            'slack_settings',
            'teams_settings',
            'discord_settings',
            'video_conferencing_settings',
            'communication_preferences',
            'quiet_hours_start',
            'quiet_hours_end',
        ];
    }

    public function getSmtpHost(): ?string
    {
        return $this->get('smtp_host');
    }

    public function setSmtpHost(?string $host): self
    {
        $this->set('smtp_host', $host);
        return $this;
    }

    public function getSmtpPort(): ?int
    {
        return $this->get('smtp_port');
    }

    public function setSmtpPort(?int $port): self
    {
        $this->set('smtp_port', $port);
        return $this;
    }

    public function getSmtpEncryption(): ?string
    {
        return $this->get('smtp_encryption');
    }

    public function setSmtpEncryption(?string $encryption): self
    {
        $this->set('smtp_encryption', $encryption);
        return $this;
    }

    public function getSmtpUsername(): ?string
    {
        return $this->get('smtp_username');
    }

    public function setSmtpUsername(?string $username): self
    {
        $this->set('smtp_username', $username);
        return $this;
    }

    public function getSmtpPassword(): ?string
    {
        return $this->get('smtp_password');
    }

    public function setSmtpPassword(?string $password): self
    {
        $this->set('smtp_password', $password);
        return $this;
    }

    public function getSmtpTimeout(): int
    {
        return $this->get('smtp_timeout', 30);
    }

    public function setSmtpTimeout(int $timeout): self
    {
        $this->set('smtp_timeout', $timeout);
        return $this;
    }

    public function hasSmtpConfiguration(): bool
    {
        return ! empty($this->getSmtpHost()) && ! empty($this->getSmtpPort());
    }

    public function getMailFromEmail(): ?string
    {
        return $this->get('mail_from_email');
    }

    public function setMailFromEmail(?string $email): self
    {
        $this->set('mail_from_email', $email);
        return $this;
    }

    public function getMailFromName(): ?string
    {
        return $this->get('mail_from_name');
    }

    public function setMailFromName(?string $name): self
    {
        $this->set('mail_from_name', $name);
        return $this;
    }

    public function getEmailRetryAttempts(): int
    {
        return $this->get('email_retry_attempts', 3);
    }

    public function setEmailRetryAttempts(int $attempts): self
    {
        $this->set('email_retry_attempts', $attempts);
        return $this;
    }

    public function isEmailTrackingEnabled(): bool
    {
        return (bool) $this->get('email_tracking_enabled', false);
    }

    public function setEmailTrackingEnabled(bool $enabled): self
    {
        $this->set('email_tracking_enabled', $enabled);
        return $this;
    }

    public function getImapHost(): ?string
    {
        return $this->get('imap_host');
    }

    public function setImapHost(?string $host): self
    {
        $this->set('imap_host', $host);
        return $this;
    }

    public function getImapPort(): ?int
    {
        return $this->get('imap_port');
    }

    public function setImapPort(?int $port): self
    {
        $this->set('imap_port', $port);
        return $this;
    }

    public function getImapEncryption(): ?string
    {
        return $this->get('imap_encryption');
    }

    public function setImapEncryption(?string $encryption): self
    {
        $this->set('imap_encryption', $encryption);
        return $this;
    }

    public function getImapUsername(): ?string
    {
        return $this->get('imap_username');
    }

    public function setImapUsername(?string $username): self
    {
        $this->set('imap_username', $username);
        return $this;
    }

    public function getImapPassword(): ?string
    {
        return $this->get('imap_password');
    }

    public function setImapPassword(?string $password): self
    {
        $this->set('imap_password', $password);
        return $this;
    }

    public function hasImapConfiguration(): bool
    {
        return ! empty($this->getImapHost()) && ! empty($this->getImapPort());
    }

    public function getSmsSettings(): ?array
    {
        return $this->get('sms_settings');
    }

    public function setSmsSettings(?array $settings): self
    {
        $this->set('sms_settings', $settings);
        return $this;
    }

    public function getVoiceSettings(): ?array
    {
        return $this->get('voice_settings');
    }

    public function setVoiceSettings(?array $settings): self
    {
        $this->set('voice_settings', $settings);
        return $this;
    }

    public function getSlackSettings(): ?array
    {
        return $this->get('slack_settings');
    }

    public function setSlackSettings(?array $settings): self
    {
        $this->set('slack_settings', $settings);
        return $this;
    }

    public function getTeamsSettings(): ?array
    {
        return $this->get('teams_settings');
    }

    public function setTeamsSettings(?array $settings): self
    {
        $this->set('teams_settings', $settings);
        return $this;
    }

    public function getQuietHoursStart(): ?string
    {
        return $this->get('quiet_hours_start');
    }

    public function setQuietHoursStart(?string $time): self
    {
        $this->set('quiet_hours_start', $time);
        return $this;
    }

    public function getQuietHoursEnd(): ?string
    {
        return $this->get('quiet_hours_end');
    }

    public function setQuietHoursEnd(?string $time): self
    {
        $this->set('quiet_hours_end', $time);
        return $this;
    }
}
