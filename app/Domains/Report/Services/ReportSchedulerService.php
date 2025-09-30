<?php

namespace App\Domains\Report\Services;

use App\Domains\Report\Models\Report;
use App\Domains\Report\Models\ReportSchedule;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Report Scheduler Service
 *
 * Automated report generation and delivery
 */
class ReportSchedulerService
{
    protected ExecutiveReportService $executiveService;

    protected ExportService $exportService;

    public function __construct(
        ExecutiveReportService $executiveService,
        ExportService $exportService
    ) {
        $this->executiveService = $executiveService;
        $this->exportService = $exportService;
    }

    /**
     * Process all scheduled reports that are due
     */
    public function processDueReports(): array
    {
        $dueReports = $this->getDueReports();
        $processed = [];
        $errors = [];

        foreach ($dueReports as $schedule) {
            try {
                $result = $this->generateAndDeliverReport($schedule);
                $processed[] = $result;

                // Update next run date
                $this->updateNextRunDate($schedule);

                Log::info('Scheduled report processed successfully', [
                    'schedule_id' => $schedule->id,
                    'report_type' => $schedule->report_type,
                ]);

            } catch (\Exception $e) {
                $errors[] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to process scheduled report', [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'processed' => count($processed),
            'errors' => count($errors),
            'details' => $processed,
            'error_details' => $errors,
        ];
    }

    /**
     * Create a new report schedule
     */
    public function createSchedule(array $data): ReportSchedule
    {
        $schedule = ReportSchedule::create([
            'company_id' => $data['company_id'],
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'report_type' => $data['report_type'],
            'frequency' => $data['frequency'], // daily, weekly, monthly, quarterly
            'parameters' => $data['parameters'] ?? [],
            'recipients' => $data['recipients'],
            'format' => $data['format'] ?? 'pdf', // pdf, excel, csv
            'is_active' => $data['is_active'] ?? true,
            'next_run_at' => $this->calculateNextRunDate($data['frequency'], $data['start_date'] ?? now()),
            'timezone' => $data['timezone'] ?? 'UTC',
            'delivery_options' => $data['delivery_options'] ?? [],
        ]);

        return $schedule;
    }

    /**
     * Update an existing schedule
     */
    public function updateSchedule(ReportSchedule $schedule, array $data): ReportSchedule
    {
        $schedule->update($data);

        // Recalculate next run date if frequency changed
        if (isset($data['frequency'])) {
            $schedule->update([
                'next_run_at' => $this->calculateNextRunDate($data['frequency']),
            ]);
        }

        return $schedule;
    }

    /**
     * Generate and deliver a scheduled report
     */
    public function generateAndDeliverReport(ReportSchedule $schedule): array
    {
        // Generate the report data
        $reportData = $this->generateReportData($schedule);

        // Export to requested format
        $exportResult = $this->exportReport($schedule, $reportData);

        // Deliver the report
        $deliveryResult = $this->deliverReport($schedule, $exportResult);

        // Log the execution
        $this->logReportExecution($schedule, $exportResult, $deliveryResult);

        return [
            'schedule_id' => $schedule->id,
            'report_type' => $schedule->report_type,
            'file_path' => $exportResult['file_path'],
            'file_size' => $exportResult['file_size'],
            'delivery_status' => $deliveryResult['status'],
            'recipients_notified' => $deliveryResult['recipients_count'],
            'generated_at' => now(),
        ];
    }

    /**
     * Preview a report without scheduling
     */
    public function previewReport(string $reportType, array $parameters, int $companyId): array
    {
        $mockSchedule = new ReportSchedule([
            'company_id' => $companyId,
            'report_type' => $reportType,
            'parameters' => $parameters,
        ]);

        return $this->generateReportData($mockSchedule);
    }

    /**
     * Get all scheduled reports for a company
     */
    public function getScheduledReports(int $companyId): Collection
    {
        return ReportSchedule::where('company_id', $companyId)
            ->with(['lastExecution'])
            ->orderBy('next_run_at')
            ->get();
    }

    /**
     * Get reports due for processing
     */
    protected function getDueReports(): Collection
    {
        return ReportSchedule::where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->with(['company'])
            ->get();
    }

    /**
     * Generate report data based on schedule configuration
     */
    protected function generateReportData(ReportSchedule $schedule): array
    {
        $parameters = $schedule->parameters ?? [];
        $companyId = $schedule->company_id;

        // Calculate date range based on frequency
        $dateRange = $this->calculateDateRange($schedule->frequency, $parameters);

        switch ($schedule->report_type) {
            case 'executive_dashboard':
                return $this->executiveService->generateMonthlyExecutiveReport(
                    $companyId,
                    $dateRange['start'],
                    $dateRange['end']
                );

            case 'qbr':
                return $this->executiveService->generateQBR(
                    $companyId,
                    $dateRange['start'],
                    $dateRange['end']
                );

            case 'client_health':
                return $this->executiveService->generateClientHealthScorecard($companyId);

            case 'sla_report':
                return $this->executiveService->generateSLAReport(
                    $companyId,
                    $dateRange['start'],
                    $dateRange['end']
                );

            case 'financial_summary':
                return $this->generateFinancialSummary($companyId, $dateRange);

            case 'service_metrics':
                return $this->generateServiceMetrics($companyId, $dateRange);

            default:
                throw new \InvalidArgumentException("Unknown report type: {$schedule->report_type}");
        }
    }

    /**
     * Export report to specified format
     */
    protected function exportReport(ReportSchedule $schedule, array $reportData): array
    {
        $filename = $this->generateFilename($schedule);

        switch ($schedule->format) {
            case 'pdf':
                return $this->exportService->exportToPDF($reportData, $filename, [
                    'template' => $schedule->report_type,
                    'company_id' => $schedule->company_id,
                ]);

            case 'excel':
                return $this->exportService->exportToExcel($reportData, $filename);

            case 'csv':
                return $this->exportService->exportToCSV($reportData, $filename);

            default:
                throw new \InvalidArgumentException("Unsupported export format: {$schedule->format}");
        }
    }

    /**
     * Deliver the report to recipients
     */
    protected function deliverReport(ReportSchedule $schedule, array $exportResult): array
    {
        $recipients = $schedule->recipients;
        $deliveryOptions = $schedule->delivery_options ?? [];
        $successCount = 0;
        $errors = [];

        foreach ($recipients as $recipient) {
            try {
                if ($recipient['type'] === 'email') {
                    $this->sendEmailReport($recipient, $schedule, $exportResult, $deliveryOptions);
                    $successCount++;
                } elseif ($recipient['type'] === 'slack') {
                    $this->sendSlackReport($recipient, $schedule, $exportResult, $deliveryOptions);
                    $successCount++;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'recipient' => $recipient,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'status' => empty($errors) ? 'success' : 'partial',
            'recipients_count' => $successCount,
            'errors' => $errors,
        ];
    }

    /**
     * Send report via email
     */
    protected function sendEmailReport(
        array $recipient,
        ReportSchedule $schedule,
        array $exportResult,
        array $deliveryOptions
    ): void {
        $user = User::find($recipient['user_id']);
        $subject = $deliveryOptions['email_subject'] ?? "Scheduled Report: {$schedule->name}";

        Mail::send('emails.scheduled-report', [
            'schedule' => $schedule,
            'user' => $user,
            'deliveryOptions' => $deliveryOptions,
        ], function ($message) use ($user, $subject, $exportResult) {
            $message->to($user->email, $user->name)
                ->subject($subject)
                ->attach($exportResult['file_path']);
        });
    }

    /**
     * Send report via Slack (if integration exists)
     */
    protected function sendSlackReport(
        array $recipient,
        ReportSchedule $schedule,
        array $exportResult,
        array $deliveryOptions
    ): void {
        // Implementation would depend on Slack integration
        // This is a placeholder for the Slack notification logic
        Log::info('Slack report delivery not yet implemented', [
            'schedule_id' => $schedule->id,
            'recipient' => $recipient,
        ]);
    }

    /**
     * Calculate the next run date based on frequency
     */
    protected function calculateNextRunDate(string $frequency, ?Carbon $currentDate = null): Carbon
    {
        $date = $currentDate ?? now();

        switch ($frequency) {
            case 'daily':
                return $date->copy()->addDay();
            case 'weekly':
                return $date->copy()->addWeek();
            case 'monthly':
                return $date->copy()->addMonth();
            case 'quarterly':
                return $date->copy()->addMonths(3);
            default:
                throw new \InvalidArgumentException("Invalid frequency: {$frequency}");
        }
    }

    /**
     * Update the next run date for a schedule
     */
    protected function updateNextRunDate(ReportSchedule $schedule): void
    {
        $nextRun = $this->calculateNextRunDate($schedule->frequency, $schedule->next_run_at);
        $schedule->update(['next_run_at' => $nextRun]);
    }

    /**
     * Calculate date range based on frequency
     */
    protected function calculateDateRange(string $frequency, array $parameters = []): array
    {
        $endDate = now();

        switch ($frequency) {
            case 'daily':
                $startDate = $endDate->copy()->subDay();
                break;
            case 'weekly':
                $startDate = $endDate->copy()->subWeek();
                break;
            case 'monthly':
                $startDate = $endDate->copy()->subMonth();
                break;
            case 'quarterly':
                $startDate = $endDate->copy()->subMonths(3);
                break;
            default:
                $startDate = $endDate->copy()->subMonth();
        }

        // Allow custom date ranges from parameters
        if (isset($parameters['custom_start_date'])) {
            $startDate = Carbon::parse($parameters['custom_start_date']);
        }
        if (isset($parameters['custom_end_date'])) {
            $endDate = Carbon::parse($parameters['custom_end_date']);
        }

        return [
            'start' => $startDate,
            'end' => $endDate,
        ];
    }

    /**
     * Generate filename for the report
     */
    protected function generateFilename(ReportSchedule $schedule): string
    {
        $date = now()->format('Y-m-d');
        $reportType = str_replace('_', '-', $schedule->report_type);
        $extension = $schedule->format;

        return "report-{$reportType}-{$date}.{$extension}";
    }

    /**
     * Log report execution
     */
    protected function logReportExecution(
        ReportSchedule $schedule,
        array $exportResult,
        array $deliveryResult
    ): void {
        // This could create a ReportExecution model to track history
        Log::info('Report execution completed', [
            'schedule_id' => $schedule->id,
            'file_size' => $exportResult['file_size'],
            'delivery_status' => $deliveryResult['status'],
            'recipients_notified' => $deliveryResult['recipients_count'],
        ]);
    }

    /**
     * Generate financial summary report
     */
    protected function generateFinancialSummary(int $companyId, array $dateRange): array
    {
        // Implementation for financial summary report
        return [
            'type' => 'financial_summary',
            'period' => $dateRange,
            'data' => [], // Financial summary data
        ];
    }

    /**
     * Generate service metrics report
     */
    protected function generateServiceMetrics(int $companyId, array $dateRange): array
    {
        // Implementation for service metrics report
        return [
            'type' => 'service_metrics',
            'period' => $dateRange,
            'data' => [], // Service metrics data
        ];
    }
}
