<?php

namespace App\Mail;

use App\Domains\Security\Models\SuspiciousLoginAttempt;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SuspiciousLoginAttemptMail extends Mailable
{
    use SerializesModels;

    public SuspiciousLoginAttempt $attempt;

    public function __construct(SuspiciousLoginAttempt $attempt)
    {
        $this->attempt = $attempt;
    }

    public function build()
    {
        return $this->subject('Suspicious Login Attempt Detected - Approval Required')
            ->view('emails.security.suspicious-login-attempt')
            ->with([
                'attempt' => $this->attempt,
                'user' => $this->attempt->user,
                'approvalUrl' => $this->attempt->getApprovalUrl(),
                'denialUrl' => $this->attempt->getDenialUrl(),
                'location' => $this->attempt->getLocationString(),
                'device' => $this->attempt->getDeviceString(),
                'riskLevel' => $this->attempt->getRiskLevelString(),
                'riskColor' => $this->attempt->getRiskLevelColor(),
                'reasons' => $this->attempt->getDetectionReasonsString(),
                'expiresAt' => $this->attempt->expires_at,
            ]);
    }
}
