<?php

namespace App\Domains\Lead\Services;

use App\Domains\Lead\Models\Lead;
use App\Domains\Lead\Models\LeadActivity;

class LeadScoringService
{
    /**
     * Calculate total score for a lead.
     */
    public function calculateTotalScore(Lead $lead): array
    {
        $demographicScore = $this->calculateDemographicScore($lead);
        $behavioralScore = $this->calculateBehavioralScore($lead);
        $fitScore = $this->calculateFitScore($lead);
        $urgencyScore = $this->calculateUrgencyScore($lead);

        $totalScore = $demographicScore + $behavioralScore + $fitScore + $urgencyScore;

        // Cap the total score at 100
        $totalScore = min($totalScore, 100);

        return [
            'demographic_score' => $demographicScore,
            'behavioral_score' => $behavioralScore,
            'fit_score' => $fitScore,
            'urgency_score' => $urgencyScore,
            'total_score' => $totalScore,
        ];
    }

    /**
     * Calculate demographic score based on company size, industry, etc.
     */
    protected function calculateDemographicScore(Lead $lead): int
    {
        $score = 0;

        $score += $this->scoreCompanySize($lead->company_size);
        $score += $this->scoreIndustry($lead->industry);
        $score += $this->scoreGeography($lead->country);
        $score += $this->scoreContactCompleteness($lead);

        return min($score, 50); // Max 50 points for demographic
    }

    protected function scoreCompanySize(?int $companySize): int
    {
        if (!$companySize) {
            return 0;
        }

        if ($companySize >= 500) {
            return 25; // Enterprise
        }

        if ($companySize >= 100) {
            return 20; // Large business
        }

        if ($companySize >= 50) {
            return 15; // Medium business
        }

        if ($companySize >= 10) {
            return 10; // Small business
        }

        return 5; // Very small business
    }

    protected function scoreIndustry(?string $industry): int
    {
        if (!$industry) {
            return 0;
        }

        $highValueIndustries = [
            'healthcare',
            'finance',
            'legal',
            'manufacturing',
            'technology',
            'insurance',
            'real_estate',
            'professional_services',
        ];

        if (in_array(strtolower($industry), $highValueIndustries)) {
            return 10;
        }

        return 5;
    }

    protected function scoreGeography(?string $country): int
    {
        if (!$country) {
            return 0;
        }

        $highValueCountries = ['US', 'Canada', 'UK', 'Australia'];

        if (in_array($country, $highValueCountries)) {
            return 5;
        }

        return 2;
    }

    protected function scoreContactCompleteness(Lead $lead): int
    {
        $score = 0;

        if ($lead->phone) {
            $score += 2;
        }
        if ($lead->company_name) {
            $score += 3;
        }
        if ($lead->title) {
            $score += 2;
        }
        if ($lead->website) {
            $score += 3;
        }

        return $score;
    }

    /**
     * Calculate behavioral score based on engagement activities.
     */
    protected function calculateBehavioralScore(Lead $lead): int
    {
        $score = 0;
        $activities = $lead->activities()->recent(30)->get();

        // Email engagement scoring
        $emailOpens = $activities->where('type', LeadActivity::TYPE_EMAIL_OPENED)->count();
        $emailClicks = $activities->where('type', LeadActivity::TYPE_EMAIL_CLICKED)->count();
        $emailReplies = $activities->where('type', LeadActivity::TYPE_EMAIL_REPLIED)->count();

        $score += min($emailOpens * 2, 10); // Max 10 points for opens
        $score += min($emailClicks * 5, 15); // Max 15 points for clicks
        $score += min($emailReplies * 10, 20); // Max 20 points for replies

        // Website engagement
        $websiteVisits = $activities->where('type', LeadActivity::TYPE_WEBSITE_VISIT)->count();
        $documentDownloads = $activities->where('type', LeadActivity::TYPE_DOCUMENT_DOWNLOADED)->count();
        $formSubmissions = $activities->where('type', LeadActivity::TYPE_FORM_SUBMITTED)->count();

        $score += min($websiteVisits * 1, 5); // Max 5 points for visits
        $score += min($documentDownloads * 3, 10); // Max 10 points for downloads
        $score += min($formSubmissions * 8, 15); // Max 15 points for forms

        // Call engagement
        $callsReceived = $activities->where('type', LeadActivity::TYPE_CALL_RECEIVED)->count();
        $score += min($callsReceived * 15, 30); // Max 30 points for inbound calls

        // Meeting engagement
        $meetingsCompleted = $activities->where('type', LeadActivity::TYPE_MEETING_COMPLETED)->count();
        $score += min($meetingsCompleted * 20, 40); // Max 40 points for meetings

        // Frequency scoring - more recent activity scores higher
        $recentActivities = $activities->where('activity_date', '>=', now()->subDays(7))->count();
        if ($recentActivities >= 5) {
            $score += 10;
        } elseif ($recentActivities >= 3) {
            $score += 5;
        } elseif ($recentActivities >= 1) {
            $score += 2;
        }

        return min($score, 50); // Max 50 points for behavioral
    }

    /**
     * Calculate fit score based on MSP service alignment.
     */
    protected function calculateFitScore(Lead $lead): int
    {
        $score = 0;

        $score += $this->scoreTechnologyFit($lead);
        $score += $this->scorePainPointFit($lead);
        $score += $this->scoreBudgetFit($lead);
        $score += $this->scoreDecisionMakerTitle($lead);

        return min($score, 50);
    }

    protected function scoreTechnologyFit(Lead $lead): int
    {
        $techIndicators = $this->extractTechIndicators($lead);

        $highFitTech = [
            'office_365',
            'microsoft_365',
            'azure',
            'aws',
            'google_workspace',
            'salesforce',
            'quickbooks',
            'sage',
            'vmware',
            'citrix',
            'remote_desktop',
            'vpn',
        ];

        $score = 0;
        foreach ($highFitTech as $tech) {
            if (in_array($tech, $techIndicators)) {
                $score += 3;
            }
        }

        return $score;
    }

    protected function scorePainPointFit(Lead $lead): int
    {
        $painPoints = $this->extractPainPoints($lead);
        $mspPainPoints = [
            'security_breach',
            'data_loss',
            'downtime',
            'slow_network',
            'email_issues',
            'backup_failure',
            'compliance_issues',
            'it_costs',
            'staff_turnover',
        ];

        $score = 0;
        foreach ($mspPainPoints as $pain) {
            if (in_array($pain, $painPoints)) {
                $score += 4;
            }
        }

        return $score;
    }

    protected function scoreBudgetFit(Lead $lead): int
    {
        if (!$lead->estimated_value) {
            return 0;
        }

        if ($lead->estimated_value >= 50000) {
            return 15;
        }

        if ($lead->estimated_value >= 25000) {
            return 10;
        }

        if ($lead->estimated_value >= 10000) {
            return 5;
        }

        return 0;
    }

    protected function scoreDecisionMakerTitle(Lead $lead): int
    {
        if (!$lead->title) {
            return 0;
        }

        $decisionMakerTitles = [
            'ceo', 'cto', 'cio', 'owner', 'president', 'vp', 'director',
            'manager', 'head', 'chief', 'founder',
        ];

        $titleLower = strtolower($lead->title);
        foreach ($decisionMakerTitles as $title) {
            if (str_contains($titleLower, $title)) {
                return 10;
            }
        }

        return 0;
    }

    /**
     * Calculate urgency score based on timing indicators.
     */
    protected function calculateUrgencyScore(Lead $lead): int
    {
        $score = 0;

        // Urgency keywords in notes
        $urgencyKeywords = [
            'urgent', 'asap', 'immediately', 'emergency', 'critical',
            'deadline', 'soon', 'quickly', 'fast', 'now',
        ];

        $notes = strtolower($lead->notes ?? '');
        foreach ($urgencyKeywords as $keyword) {
            if (str_contains($notes, $keyword)) {
                $score += 5;
            }
        }

        // Recent high-engagement activities
        $recentHighEngagement = $lead->activities()
            ->whereIn('type', [
                LeadActivity::TYPE_CALL_RECEIVED,
                LeadActivity::TYPE_EMAIL_REPLIED,
                LeadActivity::TYPE_MEETING_SCHEDULED,
                LeadActivity::TYPE_FORM_SUBMITTED,
            ])
            ->where('activity_date', '>=', now()->subDays(3))
            ->count();

        $score += min($recentHighEngagement * 8, 24);

        // Compliance deadline indicators
        $complianceKeywords = [
            'audit', 'compliance', 'regulation', 'gdpr', 'hipaa',
            'sox', 'pci', 'iso', 'certification', 'inspection',
        ];

        foreach ($complianceKeywords as $keyword) {
            if (str_contains($notes, $keyword)) {
                $score += 8;
                break;
            }
        }

        // Budget cycle timing (Q4 typically higher urgency)
        $currentMonth = now()->month;
        if (in_array($currentMonth, [10, 11, 12])) { // Q4
            $score += 5;
        } elseif (in_array($currentMonth, [3, 6, 9])) { // End of quarters
            $score += 3;
        }

        // Multiple recent touchpoints
        $recentTouchpoints = $lead->activities()
            ->where('activity_date', '>=', now()->subDays(7))
            ->count();

        if ($recentTouchpoints >= 5) {
            $score += 10;
        } elseif ($recentTouchpoints >= 3) {
            $score += 5;
        }

        return min($score, 50); // Max 50 points for urgency
    }

    /**
     * Extract technology indicators from lead data.
     */
    protected function extractTechIndicators(Lead $lead): array
    {
        $indicators = [];
        $text = strtolower(($lead->notes ?? '').' '.($lead->website ?? ''));

        $techKeywords = [
            'office_365' => ['office 365', 'o365', 'microsoft 365', 'm365'],
            'azure' => ['azure', 'microsoft cloud'],
            'aws' => ['aws', 'amazon web services'],
            'google_workspace' => ['google workspace', 'g suite', 'gmail business'],
            'salesforce' => ['salesforce', 'sfdc'],
            'quickbooks' => ['quickbooks', 'qb online'],
            'sage' => ['sage', 'sage 50', 'sage intacct'],
            'vmware' => ['vmware', 'vsphere', 'vcenter'],
            'citrix' => ['citrix', 'xenapp', 'xendesktop'],
            'remote_desktop' => ['remote desktop', 'rdp', 'terminal services'],
            'vpn' => ['vpn', 'virtual private network'],
        ];

        foreach ($techKeywords as $tech => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $indicators[] = $tech;
                    break;
                }
            }
        }

        return array_unique($indicators);
    }

    /**
     * Extract pain point indicators from lead data.
     */
    protected function extractPainPoints(Lead $lead): array
    {
        $painPoints = [];
        $text = strtolower(($lead->notes ?? ''));

        $painKeywords = [
            'security_breach' => ['security breach', 'hacked', 'cyber attack', 'malware', 'virus'],
            'data_loss' => ['data loss', 'lost data', 'deleted files', 'corrupted'],
            'downtime' => ['downtime', 'server down', 'system down', 'outage'],
            'slow_network' => ['slow network', 'slow internet', 'network slow', 'performance'],
            'email_issues' => ['email problems', 'email down', 'mail issues', 'outlook problems'],
            'backup_failure' => ['backup failed', 'backup issues', 'no backup', 'restore problems'],
            'compliance_issues' => ['compliance', 'audit', 'regulation', 'gdpr', 'hipaa'],
            'it_costs' => ['it costs', 'expensive', 'budget', 'save money'],
            'staff_turnover' => ['it staff', 'turnover', 'no it person', 'it help'],
        ];

        foreach ($painKeywords as $pain => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    $painPoints[] = $pain;
                    break;
                }
            }
        }

        return array_unique($painPoints);
    }

    /**
     * Update lead score and return new scores.
     */
    public function updateLeadScore(Lead $lead): array
    {
        $scores = $this->calculateTotalScore($lead);
        $lead->updateScore($scores);

        // Add scoring activity
        $lead->addActivity(
            LeadActivity::TYPE_SCORE_UPDATED,
            'Lead Score Updated',
            "Lead score updated to {$scores['total_score']} points",
            ['scores' => $scores]
        );

        return $scores;
    }

    /**
     * Bulk update scores for multiple leads.
     */
    public function bulkUpdateScores(array $leadIds): array
    {
        $results = [];
        $leads = Lead::whereIn('id', $leadIds)->get();

        foreach ($leads as $lead) {
            $results[$lead->id] = $this->updateLeadScore($lead);
        }

        return $results;
    }

    /**
     * Update scores for all leads that haven't been scored recently.
     */
    public function updateStaleScores(int $hoursThreshold = 24): int
    {
        $staleLeads = Lead::where(function ($query) use ($hoursThreshold) {
            $query->whereNull('last_scored_at')
                ->orWhere('last_scored_at', '<', now()->subHours($hoursThreshold));
        })->get();

        $count = 0;
        foreach ($staleLeads as $lead) {
            $this->updateLeadScore($lead);
            $count++;
        }

        return $count;
    }

    /**
     * Get leads that qualify for auto-qualification based on score.
     */
    public function getAutoQualificationCandidates(int $minScore = 70): \Illuminate\Database\Eloquent\Collection
    {
        return Lead::where('total_score', '>=', $minScore)
            ->where('status', '!=', Lead::STATUS_QUALIFIED)
            ->where('status', '!=', Lead::STATUS_CONVERTED)
            ->where('status', '!=', Lead::STATUS_LOST)
            ->orderBy('total_score', 'desc')
            ->get();
    }

    /**
     * Auto-qualify high-scoring leads.
     */
    public function autoQualifyHighScoringLeads(int $minScore = 80): int
    {
        $candidates = $this->getAutoQualificationCandidates($minScore);
        $count = 0;

        foreach ($candidates as $lead) {
            $lead->markAsQualified();
            $lead->addActivity(
                LeadActivity::TYPE_QUALIFIED,
                'Auto-Qualified',
                "Lead auto-qualified with score of {$lead->total_score}"
            );
            $count++;
        }

        return $count;
    }

    /**
     * Get scoring distribution for analytics.
     */
    public function getScoringDistribution(int $companyId): array
    {
        $leads = Lead::where('company_id', $companyId)->get();

        $distribution = [
            'excellent' => $leads->where('total_score', '>=', 80)->count(),
            'good' => $leads->whereBetween('total_score', [60, 79])->count(),
            'fair' => $leads->whereBetween('total_score', [40, 59])->count(),
            'poor' => $leads->where('total_score', '<', 40)->count(),
        ];

        $distribution['total'] = array_sum($distribution);

        return $distribution;
    }
}
