<?php

namespace Foleybridge\Nestogy\Domains\Report\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Foleybridge\Nestogy\Domains\Report\Models\Report;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Export Service
 *
 * Comprehensive export service for generating reports in various formats
 * including PDF, CSV, and Excel with professional styling and layouts.
 */
class ExportService
{
    protected ReportService $reportService;

    protected string $tempPath;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
        $this->tempPath = storage_path('app/temp/reports');

        // Ensure temp directory exists
        if (! file_exists($this->tempPath)) {
            mkdir($this->tempPath, 0755, true);
        }
    }

    /**
     * Export report in specified format
     */
    public function exportReport(string $reportType, string $format, array $dateRange, array $filters = []): array
    {
        // Generate report data
        $data = $this->generateReportData($reportType, $dateRange, $filters);

        // Generate filename
        $filename = $this->generateFilename($reportType, $format, $dateRange);

        // Export based on format
        $filePath = match (strtolower($format)) {
            'pdf' => $this->exportToPDF($data, $filename, $reportType),
            'csv' => $this->exportToCSV($data, $filename),
            'xlsx' => $this->exportToExcel($data, $filename, $reportType),
            'json' => $this->exportToJSON($data, $filename),
            default => throw new \InvalidArgumentException("Unsupported export format: {$format}")
        };

        return [
            'path' => $filePath,
            'filename' => basename($filePath),
            'size' => filesize($filePath),
            'mime_type' => $this->getMimeType($format),
        ];
    }

    /**
     * Export saved report
     */
    public function exportSavedReport(Report $report, ?string $format = null): array
    {
        $format = $format ?? $report->export_format;
        $config = $report->getConfigurationAttribute();

        return $this->exportReport(
            $report->report_type,
            $format,
            $config['date_range'],
            $config['filters'] ?? []
        );
    }

    /**
     * Export to PDF
     */
    protected function exportToPDF(array $data, string $filename, string $reportType): string
    {
        $viewData = [
            'data' => $data,
            'reportType' => $reportType,
            'generatedAt' => Carbon::now(),
            'title' => $this->getReportTitle($reportType),
            'company' => config('app.name', 'Company Name'),
        ];

        $pdf = PDF::loadView('reports.exports.pdf.template', $viewData)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isRemoteEnabled' => true,
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
                'margin_top' => 10,
                'margin_bottom' => 10,
                'margin_left' => 10,
                'margin_right' => 10,
            ]);

        $filePath = $this->tempPath.'/'.$filename;
        $pdf->save($filePath);

        return $filePath;
    }

    /**
     * Export to CSV
     */
    protected function exportToCSV(array $data, string $filename): string
    {
        $filePath = $this->tempPath.'/'.$filename;
        $handle = fopen($filePath, 'w');

        // Write headers
        if (! empty($data['tables'])) {
            $firstTable = reset($data['tables']);
            if (! empty($firstTable['data'])) {
                $headers = array_keys(reset($firstTable['data']));
                fputcsv($handle, $headers);

                // Write data rows
                foreach ($firstTable['data'] as $row) {
                    fputcsv($handle, $row);
                }
            }
        }

        fclose($handle);

        return $filePath;
    }

    /**
     * Export to Excel
     */
    protected function exportToExcel(array $data, string $filename, string $reportType): string
    {
        $filePath = $this->tempPath.'/'.$filename;

        Excel::store(new ReportExcelExport($data, $reportType), basename($filePath), 'temp');

        return $filePath;
    }

    /**
     * Export to JSON
     */
    protected function exportToJSON(array $data, string $filename): string
    {
        $filePath = $this->tempPath.'/'.$filename;

        file_put_contents($filePath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $filePath;
    }

    /**
     * Generate report data based on type
     */
    protected function generateReportData(string $reportType, array $dateRange, array $filters): array
    {
        $start = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);
        $dateRange = ['start' => $start, 'end' => $end];

        return match ($reportType) {
            'financial' => $this->reportService->getFinancialReport($dateRange, $filters['type'] ?? 'overview'),
            'tickets' => $this->reportService->getTicketReport($dateRange, $filters['type'] ?? 'overview'),
            'assets' => $this->reportService->getAssetReport($dateRange, $filters['type'] ?? 'overview'),
            'clients' => $this->reportService->getClientReport($dateRange, $filters['type'] ?? 'overview'),
            'projects' => $this->reportService->getProjectReport($dateRange, $filters['type'] ?? 'overview'),
            'users' => $this->reportService->getUserReport($dateRange, $filters['type'] ?? 'overview'),
            'dashboard' => $this->reportService->getDashboardOverview($dateRange),
            default => throw new \InvalidArgumentException("Unsupported report type: {$reportType}")
        };
    }

    /**
     * Generate filename for export
     */
    protected function generateFilename(string $reportType, string $format, array $dateRange): string
    {
        $start = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);

        $dateString = $start->format('Y-m-d').'_to_'.$end->format('Y-m-d');
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');

        return "{$reportType}_report_{$dateString}_{$timestamp}.{$format}";
    }

    /**
     * Get MIME type for format
     */
    protected function getMimeType(string $format): string
    {
        return match (strtolower($format)) {
            'pdf' => 'application/pdf',
            'csv' => 'text/csv',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'json' => 'application/json',
            default => 'application/octet-stream'
        };
    }

    /**
     * Get report title
     */
    protected function getReportTitle(string $reportType): string
    {
        return match ($reportType) {
            'financial' => 'Financial Report',
            'tickets' => 'Ticket Analytics Report',
            'assets' => 'Asset Management Report',
            'clients' => 'Client Analysis Report',
            'projects' => 'Project Management Report',
            'users' => 'User Performance Report',
            'dashboard' => 'Dashboard Overview Report',
            default => Str::title($reportType).' Report'
        };
    }

    /**
     * Clean up temporary files older than 24 hours
     */
    public function cleanupTempFiles(): int
    {
        $cleaned = 0;
        $files = glob($this->tempPath.'/*');

        foreach ($files as $file) {
            if (is_file($file) && (time() - filemtime($file)) > 86400) { // 24 hours
                unlink($file);
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get export statistics
     */
    public function getExportStatistics(): array
    {
        $files = glob($this->tempPath.'/*');

        $stats = [
            'total_files' => 0,
            'total_size' => 0,
            'formats' => [],
            'oldest_file' => null,
            'newest_file' => null,
        ];

        foreach ($files as $file) {
            if (! is_file($file)) {
                continue;
            }

            $stats['total_files']++;
            $stats['total_size'] += filesize($file);

            $extension = pathinfo($file, PATHINFO_EXTENSION);
            $stats['formats'][$extension] = ($stats['formats'][$extension] ?? 0) + 1;

            $fileTime = filemtime($file);
            if (! $stats['oldest_file'] || $fileTime < filemtime($stats['oldest_file'])) {
                $stats['oldest_file'] = $file;
            }
            if (! $stats['newest_file'] || $fileTime > filemtime($stats['newest_file'])) {
                $stats['newest_file'] = $file;
            }
        }

        return $stats;
    }

    /**
     * Schedule automated exports
     */
    public function scheduleExport(Report $report, array $config = []): bool
    {
        if (! $report->isScheduled()) {
            return false;
        }

        try {
            $exportData = $this->exportSavedReport($report);

            // Send email with attachment if configured
            if ($config['send_email'] ?? true) {
                $this->sendReportEmail($report, $exportData);
            }

            // Store in configured location
            if ($config['store_path'] ?? null) {
                $this->storeReport($exportData, $config['store_path']);
            }

            $report->markAsGenerated();

            return true;
        } catch (\Exception $e) {
            \Log::error('Failed to schedule export for report '.$report->id.': '.$e->getMessage());

            return false;
        }
    }

    /**
     * Send report via email
     */
    protected function sendReportEmail(Report $report, array $exportData): void
    {
        // Implementation would integrate with Laravel's Mail system
        // This is a placeholder for the email functionality
        \Log::info("Email sent for report {$report->name} to recipients: ".implode(', ', $report->recipients ?? []));
    }

    /**
     * Store report in specified location
     */
    protected function storeReport(array $exportData, string $storePath): void
    {
        Storage::disk('local')->put($storePath.'/'.$exportData['filename'], file_get_contents($exportData['path']));
    }
}

/**
 * Excel Export Class
 */
class ReportExcelExport implements FromCollection, WithHeadings, WithStyles, WithTitle
{
    protected array $data;

    protected string $reportType;

    public function __construct(array $data, string $reportType)
    {
        $this->data = $data;
        $this->reportType = $reportType;
    }

    public function collection()
    {
        $rows = collect();

        // Add summary data
        if (isset($this->data['summary'])) {
            $rows->push(['Summary']);
            foreach ($this->data['summary'] as $key => $value) {
                $rows->push([Str::title(str_replace('_', ' ', $key)), $value]);
            }
            $rows->push(['']); // Empty row
        }

        // Add table data
        if (isset($this->data['tables'])) {
            foreach ($this->data['tables'] as $tableName => $tableData) {
                $rows->push([Str::title(str_replace('_', ' ', $tableName))]);

                if (! empty($tableData['data'])) {
                    // Add headers
                    $headers = array_keys(reset($tableData['data']));
                    $rows->push($headers);

                    // Add data rows
                    foreach ($tableData['data'] as $row) {
                        $rows->push(array_values($row));
                    }
                }

                $rows->push(['']); // Empty row between tables
            }
        }

        return $rows;
    }

    public function headings(): array
    {
        return [
            'Item',
            'Value',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 14]],
            'A:B' => ['alignment' => ['horizontal' => 'left']],
        ];
    }

    public function title(): string
    {
        return Str::title($this->reportType).' Report';
    }
}
