<?php

namespace App\Domains\Ticket\Services;

use App\Domains\Ticket\Models\Ticket;
use App\Domains\Ticket\Models\TimeEntryTemplate;
use Illuminate\Support\Collection;

/**
 * Work Type Classification Service
 * 
 * Intelligently categorizes tickets and suggests appropriate work types
 * based on keyword analysis and pattern matching.
 */
class WorkTypeClassificationService
{
    /**
     * Classification patterns for common MSP work types
     */
    protected array $classificationPatterns = [
        'account_management' => [
            'keywords' => ['password', 'reset', 'login', 'account', 'locked', 'user', 'access', 'permissions'],
            'weight' => 1.0,
            'description' => 'Account and user management tasks',
        ],
        'email_support' => [
            'keywords' => ['email', 'outlook', 'mail', 'smtp', 'imap', 'exchange', 'inbox', 'send', 'receive'],
            'weight' => 1.0,
            'description' => 'Email configuration and support',
        ],
        'network_support' => [
            'keywords' => ['network', 'internet', 'connection', 'wifi', 'ethernet', 'router', 'switch', 'firewall', 'vpn'],
            'weight' => 1.2,
            'description' => 'Network connectivity and infrastructure',
        ],
        'software_support' => [
            'keywords' => ['install', 'software', 'application', 'program', 'app', 'update', 'upgrade', 'license'],
            'weight' => 1.0,
            'description' => 'Software installation and support',
        ],
        'backup_recovery' => [
            'keywords' => ['backup', 'recovery', 'restore', 'data', 'file', 'lost', 'corrupt', 'archive'],
            'weight' => 1.3,
            'description' => 'Data backup and recovery operations',
        ],
        'security_support' => [
            'keywords' => ['virus', 'malware', 'security', 'threat', 'infected', 'antivirus', 'scan', 'quarantine'],
            'weight' => 1.5,
            'description' => 'Security incidents and threats',
        ],
        'maintenance' => [
            'keywords' => ['maintenance', 'update', 'patch', 'system', 'routine', 'cleanup', 'optimize'],
            'weight' => 0.9,
            'description' => 'System maintenance and updates',
        ],
        'hardware_support' => [
            'keywords' => ['hardware', 'printer', 'computer', 'laptop', 'monitor', 'keyboard', 'mouse', 'device'],
            'weight' => 1.1,
            'description' => 'Hardware issues and setup',
        ],
        'troubleshooting' => [
            'keywords' => ['error', 'problem', 'issue', 'not working', 'broken', 'crash', 'freeze', 'slow'],
            'weight' => 0.8,
            'description' => 'General troubleshooting and diagnostics',
        ],
    ];

    /**
     * Classify ticket and suggest work type
     */
    public function classifyTicket(Ticket $ticket): array
    {
        $text = strtolower($ticket->subject . ' ' . $ticket->details);
        $scores = [];

        foreach ($this->classificationPatterns as $workType => $pattern) {
            $score = $this->calculateScore($text, $pattern);
            if ($score > 0) {
                $scores[$workType] = $score;
            }
        }

        // Sort by score descending
        arsort($scores);

        return [
            'suggested_work_type' => array_key_first($scores),
            'confidence' => !empty($scores) ? round(reset($scores), 2) : 0,
            'all_scores' => $scores,
        ];
    }

    /**
     * Get template suggestions for a ticket
     */
    public function getTemplateSuggestions(Ticket $ticket, int $limit = 5): Collection
    {
        return TimeEntryTemplate::getSuggestionsForTicket(
            $ticket->company_id,
            $ticket->subject,
            $ticket->details,
            $limit
        );
    }

    /**
     * Get smart work type suggestions based on ticket content
     */
    public function getWorkTypeSuggestions(Ticket $ticket): array
    {
        $classification = $this->classifyTicket($ticket);
        $templates = $this->getTemplateSuggestions($ticket, 3);

        $suggestions = [];

        // Add classification-based suggestion
        if ($classification['suggested_work_type'] && $classification['confidence'] > 20) {
            $suggestions[] = [
                'work_type' => $classification['suggested_work_type'],
                'confidence' => $classification['confidence'],
                'source' => 'classification',
                'description' => $this->classificationPatterns[$classification['suggested_work_type']]['description'],
            ];
        }

        // Add template-based suggestions
        foreach ($templates as $suggestion) {
            $suggestions[] = [
                'work_type' => $suggestion['template']->work_type,
                'confidence' => $suggestion['confidence'],
                'source' => 'template',
                'template_id' => $suggestion['template']->id,
                'template_name' => $suggestion['template']->name,
                'default_hours' => $suggestion['template']->default_hours,
                'description' => $suggestion['template']->description,
            ];
        }

        // Remove duplicates and sort by confidence
        $unique = [];
        foreach ($suggestions as $suggestion) {
            $key = $suggestion['work_type'];
            if (!isset($unique[$key]) || $unique[$key]['confidence'] < $suggestion['confidence']) {
                $unique[$key] = $suggestion;
            }
        }

        return array_values(array_slice(
            collect($unique)->sortByDesc('confidence')->toArray(),
            0,
            5
        ));
    }

    /**
     * Learn from user corrections to improve classification
     */
    public function learnFromCorrection(Ticket $ticket, string $actualWorkType, string $suggestedWorkType): void
    {
        // This could be implemented to improve the classification algorithm
        // For now, we'll just log the correction for future analysis
        \Log::info('Work type classification correction', [
            'ticket_id' => $ticket->id,
            'subject' => $ticket->subject,
            'suggested' => $suggestedWorkType,
            'actual' => $actualWorkType,
            'company_id' => $ticket->company_id,
        ]);
    }

    /**
     * Get work type statistics for a company
     */
    public function getWorkTypeStats(int $companyId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        $stats = \DB::table('ticket_time_entries')
            ->where('company_id', $companyId)
            ->where('created_at', '>=', $startDate)
            ->select('work_type', \DB::raw('COUNT(*) as count'), \DB::raw('SUM(hours_worked) as total_hours'))
            ->groupBy('work_type')
            ->orderByDesc('count')
            ->get();

        return $stats->map(function ($stat) {
            return [
                'work_type' => $stat->work_type,
                'count' => $stat->count,
                'total_hours' => round($stat->total_hours, 2),
                'avg_hours' => round($stat->total_hours / $stat->count, 2),
                'description' => $this->classificationPatterns[$stat->work_type]['description'] ?? 'Unknown work type',
            ];
        })->toArray();
    }

    /**
     * Calculate classification score for text against pattern
     */
    protected function calculateScore(string $text, array $pattern): float
    {
        $matches = 0;
        $totalKeywords = count($pattern['keywords']);

        foreach ($pattern['keywords'] as $keyword) {
            if (strpos($text, strtolower($keyword)) !== false) {
                $matches++;
            }
        }

        if ($matches === 0) {
            return 0;
        }

        // Base score is percentage of keywords matched
        $baseScore = ($matches / $totalKeywords) * 100;
        
        // Apply weight multiplier
        $weightedScore = $baseScore * $pattern['weight'];
        
        // Boost for multiple keyword matches
        if ($matches > 1) {
            $weightedScore *= (1 + ($matches - 1) * 0.1);
        }

        return min($weightedScore, 100);
    }

    /**
     * Get available work types with descriptions
     */
    public function getAvailableWorkTypes(): array
    {
        $workTypes = [];
        
        foreach ($this->classificationPatterns as $type => $pattern) {
            $workTypes[$type] = [
                'name' => ucwords(str_replace('_', ' ', $type)),
                'description' => $pattern['description'],
                'keywords' => $pattern['keywords'],
            ];
        }

        return $workTypes;
    }

    /**
     * Bulk classify tickets for analysis
     */
    public function bulkClassifyTickets(Collection $tickets): array
    {
        $results = [];
        
        foreach ($tickets as $ticket) {
            $classification = $this->classifyTicket($ticket);
            $results[] = [
                'ticket_id' => $ticket->id,
                'current_category' => $ticket->category,
                'suggested_work_type' => $classification['suggested_work_type'],
                'confidence' => $classification['confidence'],
            ];
        }

        return $results;
    }
}