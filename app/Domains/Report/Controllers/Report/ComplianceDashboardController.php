<?php

namespace App\Domains\Report\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Domains\Client\Models\ClientITDocumentation;
use App\Domains\Client\Services\ComplianceEngineService;
use App\Domains\Client\Services\DocumentationTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ComplianceDashboardController extends Controller
{
    protected ComplianceEngineService $complianceService;
    protected DocumentationTemplateService $templateService;

    public function __construct(
        ComplianceEngineService $complianceService,
        DocumentationTemplateService $templateService
    ) {
        $this->complianceService = $complianceService;
        $this->templateService = $templateService;
    }

    /**
     * Display the compliance dashboard
     */
    public function index(Request $request)
    {
        // Get compliance framework statistics
        $frameworkStats = $this->getFrameworkStatistics();
        
        // Get documents by compliance status
        $complianceDocuments = $this->getComplianceDocuments($request);
        
        // Get overall compliance score
        $overallCompliance = $this->calculateOverallComplianceScore();
        
        // Get critical gaps across all documents
        $criticalGaps = $this->getCriticalComplianceGaps();
        
        // Get upcoming audits and reviews
        $upcomingReviews = $this->getUpcomingReviews();
        
        // Get compliance trends
        $complianceTrends = $this->getComplianceTrends();
        
        return view('reports.compliance-dashboard', compact(
            'frameworkStats',
            'complianceDocuments',
            'overallCompliance',
            'criticalGaps',
            'upcomingReviews',
            'complianceTrends'
        ));
    }

    /**
     * Get framework-specific compliance report
     */
    public function frameworkReport(Request $request, string $framework)
    {
        $frameworks = $this->complianceService->getComplianceFrameworks();
        
        if (!isset($frameworks[$framework])) {
            abort(404, 'Framework not found');
        }
        
        $frameworkData = $frameworks[$framework];
        
        // Get all documents using this framework
        $documents = ClientITDocumentation::whereJsonContains('compliance_requirements', $framework)
            ->with(['client', 'author'])
            ->get();
        
        // Calculate compliance scores for each document
        $documentScores = [];
        $aggregateGaps = [];
        
        foreach ($documents as $doc) {
            $score = $this->complianceService->calculateComplianceScore($doc, $framework);
            $documentScores[] = [
                'document' => $doc,
                'score' => $score
            ];
            
            // Aggregate gaps
            foreach ($score['gaps'] as $gap) {
                $gapKey = $gap['key'];
                if (!isset($aggregateGaps[$gapKey])) {
                    $aggregateGaps[$gapKey] = [
                        'category' => $gap['category'],
                        'requirement' => $gap['requirement'],
                        'count' => 0,
                        'documents' => []
                    ];
                }
                $aggregateGaps[$gapKey]['count']++;
                $aggregateGaps[$gapKey]['documents'][] = $doc->name;
            }
        }
        
        // Sort gaps by frequency
        uasort($aggregateGaps, function($a, $b) {
            return $b['count'] - $a['count'];
        });
        
        return view('reports.framework-compliance', compact(
            'framework',
            'frameworkData',
            'documents',
            'documentScores',
            'aggregateGaps'
        ));
    }

    /**
     * Export compliance report
     */
    public function export(Request $request)
    {
        $format = $request->get('format', 'pdf');
        $framework = $request->get('framework');
        $documentId = $request->get('document_id');
        
        // Implementation for exporting reports
        // This would generate PDF/Excel reports based on the format
        
        return response()->json(['message' => 'Export functionality coming soon']);
    }

    /**
     * Get framework statistics
     */
    private function getFrameworkStatistics(): array
    {
        $frameworks = ['gdpr', 'hipaa', 'soc2', 'pci_dss', 'iso27001', 'nist_csf'];
        $stats = [];
        
        foreach ($frameworks as $framework) {
            $count = ClientITDocumentation::whereJsonContains('compliance_requirements', $framework)
                ->count();
            
            $avgScore = 0;
            if ($count > 0) {
                $documents = ClientITDocumentation::whereJsonContains('compliance_requirements', $framework)
                    ->limit(10)
                    ->get();
                
                $totalScore = 0;
                foreach ($documents as $doc) {
                    $result = $this->complianceService->calculateComplianceScore($doc, $framework);
                    $totalScore += $result['score'];
                }
                $avgScore = $totalScore / min($count, 10);
            }
            
            $stats[$framework] = [
                'name' => $this->getFrameworkName($framework),
                'count' => $count,
                'average_score' => round($avgScore, 1)
            ];
        }
        
        return $stats;
    }

    /**
     * Get documents by compliance status
     */
    private function getComplianceDocuments(Request $request)
    {
        $query = ClientITDocumentation::with(['client', 'author'])
            ->whereNotNull('compliance_requirements')
            ->where('compliance_requirements', '!=', '[]');
        
        if ($request->has('framework')) {
            $query->whereJsonContains('compliance_requirements', $request->get('framework'));
        }
        
        if ($request->has('min_score')) {
            $query->where('documentation_completeness', '>=', $request->get('min_score'));
        }
        
        return $query->orderBy('documentation_completeness', 'desc')
            ->paginate(20);
    }

    /**
     * Calculate overall compliance score
     */
    private function calculateOverallComplianceScore(): array
    {
        $totalScore = 0;
        $documentCount = 0;
        
        $documents = ClientITDocumentation::whereNotNull('compliance_requirements')
            ->where('compliance_requirements', '!=', '[]')
            ->limit(100)
            ->get();
        
        foreach ($documents as $doc) {
            if ($doc->documentation_completeness > 0) {
                $totalScore += $doc->documentation_completeness;
                $documentCount++;
            }
        }
        
        return [
            'score' => $documentCount > 0 ? round($totalScore / $documentCount, 1) : 0,
            'document_count' => $documentCount,
            'status' => $this->getComplianceStatus($documentCount > 0 ? $totalScore / $documentCount : 0)
        ];
    }

    /**
     * Get critical compliance gaps
     */
    private function getCriticalComplianceGaps(): array
    {
        $gaps = [];
        
        // Sample implementation - would need to aggregate from all documents
        $criticalRequirements = [
            'encryption' => 'Data encryption at rest and in transit',
            'access_control' => 'Role-based access control implementation',
            'audit_logs' => 'Comprehensive audit logging',
            'incident_response' => 'Incident response procedures',
            'data_retention' => 'Data retention and disposal policies'
        ];
        
        foreach ($criticalRequirements as $key => $description) {
            $missingCount = ClientITDocumentation::whereNotNull('compliance_requirements')
                ->where('encryption_required', false)
                ->count();
            
            if ($missingCount > 0) {
                $gaps[] = [
                    'requirement' => $description,
                    'affected_documents' => $missingCount,
                    'severity' => 'critical'
                ];
            }
        }
        
        return $gaps;
    }

    /**
     * Get upcoming reviews
     */
    private function getUpcomingReviews()
    {
        return ClientITDocumentation::with(['client', 'author'])
            ->where('next_review_at', '>=', now())
            ->where('next_review_at', '<=', now()->addDays(30))
            ->orderBy('next_review_at')
            ->limit(10)
            ->get();
    }

    /**
     * Get compliance trends
     */
    private function getComplianceTrends(): array
    {
        $trends = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $avgScore = ClientITDocumentation::whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->avg('documentation_completeness') ?? 0;
            
            $trends[] = [
                'month' => $month->format('M Y'),
                'score' => round($avgScore, 1)
            ];
        }
        
        return $trends;
    }

    /**
     * Get framework display name
     */
    private function getFrameworkName(string $framework): string
    {
        $names = [
            'gdpr' => 'GDPR',
            'hipaa' => 'HIPAA',
            'soc2' => 'SOC 2',
            'pci_dss' => 'PCI DSS',
            'iso27001' => 'ISO 27001',
            'nist_csf' => 'NIST CSF'
        ];
        
        return $names[$framework] ?? strtoupper($framework);
    }

    /**
     * Get compliance status based on score
     */
    private function getComplianceStatus(float $score): string
    {
        if ($score >= 90) return 'Excellent';
        if ($score >= 75) return 'Good';
        if ($score >= 60) return 'Fair';
        if ($score >= 40) return 'Poor';
        return 'Critical';
    }
}