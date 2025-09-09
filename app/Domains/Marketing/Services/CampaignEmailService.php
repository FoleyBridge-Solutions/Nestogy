<?php

namespace App\Domains\Marketing\Services;

use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadActivity;
use App\Domains\Marketing\Models\CampaignEnrollment;
use App\Domains\Marketing\Models\CampaignSequence;
use App\Domains\Marketing\Models\EmailTracking;
use App\Domains\Marketing\Models\MarketingCampaign;
use App\Models\Contact;
use App\Services\EmailService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CampaignEmailService extends EmailService
{
    /**
     * Send campaign email to enrollment.
     */
    public function sendCampaignEmail(CampaignEnrollment $enrollment): bool
    {
        try {
            $sequence = $enrollment->getCurrentSequence();
            if (!$sequence) {
                Log::warning('No sequence found for enrollment', ['enrollment_id' => $enrollment->id]);
                return false;
            }

            $recipient = $enrollment->recipient;
            if (!$recipient || !$recipient->email) {
                Log::warning('No valid recipient for enrollment', ['enrollment_id' => $enrollment->id]);
                return false;
            }

            // Generate tracking ID
            $trackingId = Str::uuid();

            // Prepare email variables
            $variables = $this->prepareEmailVariables($recipient, $enrollment);
            
            // Process email content
            $subject = $sequence->getProcessedSubjectLine($variables);
            $content = $sequence->getProcessedEmailTemplate($variables);
            
            // Add tracking pixels and links
            $content = $this->addEmailTracking($content, $trackingId);

            // Create email tracking record
            $emailTracking = $this->createEmailTracking($enrollment, $sequence, $trackingId, $subject);

            // Send the email
            $sent = Mail::send([], [], function ($message) use ($recipient, $subject, $content) {
                $message->to($recipient->email, $this->getRecipientName($recipient))
                        ->subject($subject)
                        ->html($content);
            });

            if ($sent) {
                // Update enrollment metrics
                $enrollment->recordEmailSent();
                $enrollment->advanceToNextStep();

                // Add activity to lead
                if ($enrollment->lead) {
                    $enrollment->lead->addActivity(
                        LeadActivity::TYPE_EMAIL_SENT,
                        $subject,
                        "Campaign email sent: {$enrollment->campaign->name}",
                        [
                            'campaign_id' => $enrollment->campaign_id,
                            'sequence_step' => $sequence->step_number,
                            'tracking_id' => $trackingId
                        ]
                    );
                }

                // Update email tracking
                $emailTracking->update([
                    'status' => 'sent',
                    'sent_at' => now()
                ]);

                Log::info('Campaign email sent successfully', [
                    'enrollment_id' => $enrollment->id,
                    'recipient_email' => $recipient->email,
                    'tracking_id' => $trackingId
                ]);

                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('Failed to send campaign email', [
                'enrollment_id' => $enrollment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Process campaign emails ready to be sent.
     */
    public function processPendingCampaignEmails(): int
    {
        $readyEnrollments = CampaignEnrollment::readyForEmail()
            ->with(['campaign', 'lead', 'contact'])
            ->limit(100) // Process in batches
            ->get();

        $sentCount = 0;

        foreach ($readyEnrollments as $enrollment) {
            if ($this->sendCampaignEmail($enrollment)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    /**
     * Track email open.
     */
    public function trackEmailOpen(string $trackingId, string $userAgent = null, string $ipAddress = null): bool
    {
        try {
            $tracking = EmailTracking::where('tracking_id', $trackingId)->first();
            
            if (!$tracking) {
                return false;
            }

            // Update tracking record
            $updateData = [
                'open_count' => $tracking->open_count + 1,
                'last_opened_at' => now(),
                'user_agent' => $userAgent,
                'ip_address' => $ipAddress
            ];

            if (!$tracking->first_opened_at) {
                $updateData['first_opened_at'] = now();
            }

            $tracking->update($updateData);

            // Update enrollment metrics
            if ($tracking->campaign_id) {
                $enrollment = CampaignEnrollment::where('campaign_id', $tracking->campaign_id)
                    ->where(function($q) use ($tracking) {
                        if ($tracking->lead_id) {
                            $q->where('lead_id', $tracking->lead_id);
                        } elseif ($tracking->contact_id) {
                            $q->where('contact_id', $tracking->contact_id);
                        }
                    })
                    ->first();

                if ($enrollment) {
                    $enrollment->recordEmailOpened();
                }
            }

            // Add activity to lead
            if ($tracking->lead_id) {
                $lead = Lead::find($tracking->lead_id);
                if ($lead) {
                    $lead->addActivity(
                        LeadActivity::TYPE_EMAIL_OPENED,
                        'Email Opened',
                        "Opened email: {$tracking->subject_line}",
                        [
                            'tracking_id' => $trackingId,
                            'campaign_id' => $tracking->campaign_id,
                            'user_agent' => $userAgent,
                            'ip_address' => $ipAddress
                        ]
                    );
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to track email open', [
                'tracking_id' => $trackingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Track email click.
     */
    public function trackEmailClick(string $trackingId, string $url, string $userAgent = null, string $ipAddress = null): bool
    {
        try {
            $tracking = EmailTracking::where('tracking_id', $trackingId)->first();
            
            if (!$tracking) {
                return false;
            }

            // Update tracking record
            $updateData = [
                'click_count' => $tracking->click_count + 1,
                'last_clicked_at' => now(),
                'user_agent' => $userAgent,
                'ip_address' => $ipAddress
            ];

            if (!$tracking->first_clicked_at) {
                $updateData['first_clicked_at'] = now();
            }

            $tracking->update($updateData);

            // Update enrollment metrics
            if ($tracking->campaign_id) {
                $enrollment = CampaignEnrollment::where('campaign_id', $tracking->campaign_id)
                    ->where(function($q) use ($tracking) {
                        if ($tracking->lead_id) {
                            $q->where('lead_id', $tracking->lead_id);
                        } elseif ($tracking->contact_id) {
                            $q->where('contact_id', $tracking->contact_id);
                        }
                    })
                    ->first();

                if ($enrollment) {
                    $enrollment->recordEmailClicked();
                }
            }

            // Add activity to lead
            if ($tracking->lead_id) {
                $lead = Lead::find($tracking->lead_id);
                if ($lead) {
                    $lead->addActivity(
                        LeadActivity::TYPE_EMAIL_CLICKED,
                        'Email Link Clicked',
                        "Clicked link in email: {$tracking->subject_line}",
                        [
                            'tracking_id' => $trackingId,
                            'campaign_id' => $tracking->campaign_id,
                            'url' => $url,
                            'user_agent' => $userAgent,
                            'ip_address' => $ipAddress
                        ]
                    );
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to track email click', [
                'tracking_id' => $trackingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Track email unsubscribe.
     */
    public function trackEmailUnsubscribe(string $trackingId): bool
    {
        try {
            $tracking = EmailTracking::where('tracking_id', $trackingId)->first();
            
            if (!$tracking) {
                return false;
            }

            // Update tracking record
            $tracking->update(['unsubscribed_at' => now()]);

            // Update enrollment status
            if ($tracking->campaign_id) {
                $enrollment = CampaignEnrollment::where('campaign_id', $tracking->campaign_id)
                    ->where(function($q) use ($tracking) {
                        if ($tracking->lead_id) {
                            $q->where('lead_id', $tracking->lead_id);
                        } elseif ($tracking->contact_id) {
                            $q->where('contact_id', $tracking->contact_id);
                        }
                    })
                    ->first();

                if ($enrollment) {
                    $enrollment->unsubscribe();
                }
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to track email unsubscribe', [
                'tracking_id' => $trackingId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Prepare email variables for template processing.
     */
    protected function prepareEmailVariables($recipient, CampaignEnrollment $enrollment): array
    {
        $variables = [
            'first_name' => '',
            'last_name' => '',
            'full_name' => '',
            'email' => '',
            'company_name' => '',
            'campaign_name' => $enrollment->campaign->name,
            'unsubscribe_url' => $this->generateUnsubscribeUrl($enrollment),
        ];

        if ($recipient instanceof Lead) {
            $variables = array_merge($variables, [
                'first_name' => $recipient->first_name,
                'last_name' => $recipient->last_name,
                'full_name' => $recipient->full_name,
                'email' => $recipient->email,
                'company_name' => $recipient->company_name ?? '',
                'title' => $recipient->title ?? '',
                'phone' => $recipient->phone ?? '',
            ]);
        } elseif ($recipient instanceof Contact) {
            $nameParts = explode(' ', $recipient->name, 2);
            $variables = array_merge($variables, [
                'first_name' => $nameParts[0] ?? '',
                'last_name' => $nameParts[1] ?? '',
                'full_name' => $recipient->name,
                'email' => $recipient->email,
                'company_name' => $recipient->client->company_name ?? '',
                'title' => $recipient->title ?? '',
                'phone' => $recipient->phone ?? '',
            ]);
        }

        return $variables;
    }

    /**
     * Add tracking pixels and convert links for tracking.
     */
    protected function addEmailTracking(string $content, string $trackingId): string
    {
        // Add tracking pixel
        $trackingPixel = '<img src="' . route('marketing.email.track-open', $trackingId) . '" width="1" height="1" style="display:none;" />';
        $content .= $trackingPixel;

        // Convert links for click tracking
        $content = preg_replace_callback(
            '/<a\s+([^>]*?)href="([^"]*)"([^>]*?)>/i',
            function($matches) use ($trackingId) {
                $originalUrl = $matches[2];
                $trackingUrl = route('marketing.email.track-click', [
                    'tracking_id' => $trackingId,
                    'url' => urlencode($originalUrl)
                ]);
                
                return '<a ' . $matches[1] . 'href="' . $trackingUrl . '"' . $matches[3] . '>';
            },
            $content
        );

        return $content;
    }

    /**
     * Create email tracking record.
     */
    protected function createEmailTracking(CampaignEnrollment $enrollment, CampaignSequence $sequence, string $trackingId, string $subject): EmailTracking
    {
        $recipient = $enrollment->recipient;
        
        return EmailTracking::create([
            'company_id' => $enrollment->campaign->company_id,
            'tracking_id' => $trackingId,
            'lead_id' => $enrollment->lead_id,
            'contact_id' => $enrollment->contact_id,
            'recipient_email' => $recipient->email,
            'campaign_id' => $enrollment->campaign_id,
            'campaign_sequence_id' => $sequence->id,
            'email_type' => 'campaign',
            'subject_line' => $subject,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }

    /**
     * Get recipient name.
     */
    protected function getRecipientName($recipient): string
    {
        if ($recipient instanceof Lead) {
            return $recipient->full_name;
        } elseif ($recipient instanceof Contact) {
            return $recipient->name;
        }

        return '';
    }

    /**
     * Generate unsubscribe URL.
     */
    protected function generateUnsubscribeUrl(CampaignEnrollment $enrollment): string
    {
        return route('marketing.unsubscribe', [
            'enrollment' => $enrollment->id,
            'token' => hash('sha256', $enrollment->id . $enrollment->campaign_id . config('app.key'))
        ]);
    }

    /**
     * Send test email for campaign sequence.
     */
    public function sendTestEmail(CampaignSequence $sequence, string $testEmail): bool
    {
        try {
            $variables = [
                'first_name' => 'Test',
                'last_name' => 'User',
                'full_name' => 'Test User',
                'email' => $testEmail,
                'company_name' => 'Test Company',
                'campaign_name' => $sequence->campaign->name,
                'unsubscribe_url' => '#',
            ];

            $subject = '[TEST] ' . $sequence->getProcessedSubjectLine($variables);
            $content = $sequence->getProcessedEmailTemplate($variables);

            return Mail::send([], [], function ($message) use ($testEmail, $subject, $content) {
                $message->to($testEmail)
                        ->subject($subject)
                        ->html($content);
            });

        } catch (\Exception $e) {
            Log::error('Failed to send test email', [
                'sequence_id' => $sequence->id,
                'test_email' => $testEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Get campaign email performance metrics.
     */
    public function getCampaignMetrics(MarketingCampaign $campaign): array
    {
        $emailTracking = EmailTracking::where('campaign_id', $campaign->id);

        return [
            'total_sent' => $emailTracking->count(),
            'total_delivered' => $emailTracking->where('status', 'delivered')->count(),
            'total_opens' => $emailTracking->whereNotNull('first_opened_at')->count(),
            'total_clicks' => $emailTracking->whereNotNull('first_clicked_at')->count(),
            'total_unsubscribes' => $emailTracking->whereNotNull('unsubscribed_at')->count(),
            'total_bounces' => $emailTracking->where('status', 'bounced')->count(),
            'open_rate' => $this->calculateOpenRate($campaign),
            'click_rate' => $this->calculateClickRate($campaign),
            'unsubscribe_rate' => $this->calculateUnsubscribeRate($campaign),
            'bounce_rate' => $this->calculateBounceRate($campaign),
        ];
    }

    /**
     * Calculate open rate for campaign.
     */
    protected function calculateOpenRate(MarketingCampaign $campaign): float
    {
        $delivered = EmailTracking::where('campaign_id', $campaign->id)
            ->where('status', 'delivered')
            ->count();

        if ($delivered === 0) {
            return 0;
        }

        $opened = EmailTracking::where('campaign_id', $campaign->id)
            ->whereNotNull('first_opened_at')
            ->count();

        return ($opened / $delivered) * 100;
    }

    /**
     * Calculate click rate for campaign.
     */
    protected function calculateClickRate(MarketingCampaign $campaign): float
    {
        $delivered = EmailTracking::where('campaign_id', $campaign->id)
            ->where('status', 'delivered')
            ->count();

        if ($delivered === 0) {
            return 0;
        }

        $clicked = EmailTracking::where('campaign_id', $campaign->id)
            ->whereNotNull('first_clicked_at')
            ->count();

        return ($clicked / $delivered) * 100;
    }

    /**
     * Calculate unsubscribe rate for campaign.
     */
    protected function calculateUnsubscribeRate(MarketingCampaign $campaign): float
    {
        $delivered = EmailTracking::where('campaign_id', $campaign->id)
            ->where('status', 'delivered')
            ->count();

        if ($delivered === 0) {
            return 0;
        }

        $unsubscribed = EmailTracking::where('campaign_id', $campaign->id)
            ->whereNotNull('unsubscribed_at')
            ->count();

        return ($unsubscribed / $delivered) * 100;
    }

    /**
     * Calculate bounce rate for campaign.
     */
    protected function calculateBounceRate(MarketingCampaign $campaign): float
    {
        $sent = EmailTracking::where('campaign_id', $campaign->id)->count();

        if ($sent === 0) {
            return 0;
        }

        $bounced = EmailTracking::where('campaign_id', $campaign->id)
            ->where('status', 'bounced')
            ->count();

        return ($bounced / $sent) * 100;
    }
}