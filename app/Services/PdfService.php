<?php

namespace App\Services;

use App\Contracts\Services\PdfServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfService implements PdfServiceInterface
{
    public function __construct()
    {
        // Config will be accessed via config() helper
    }

    /**
     * Get configuration value
     */
    protected function config(?string $key = null)
    {
        $config = config('pdf');
        return $key ? ($config[$key] ?? null) : $config;
    }

    /**
     * Generate PDF from view
     */
    public function generate(string $view, array $data = [], array $options = []): string
    {
        $driver = $options['driver'] ?? $this->config('default');
        $template = $options['template'] ?? 'default';

        // Get template configuration
        $templates = $this->config('templates');
        $templateConfig = $templates[$template] ?? $templates['default'];
        
        // Merge options with template config
        $pdfOptions = array_merge($templateConfig, $options);

        switch ($driver) {
            case 'spatie':
                return $this->generateWithSpatie($view, $data, $pdfOptions);
            case 'dompdf':
            default:
                return $this->generateWithDomPdf($view, $data, $pdfOptions);
        }
    }

    /**
     * Generate PDF using DomPDF
     */
    protected function generateWithDomPdf(string $view, array $data, array $options): string
    {
        $pdf = DomPDF::loadView($view, $data);

        // Set paper size and orientation
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper'], $options['orientation'] ?? 'portrait');
        }

        // Set options
        if (isset($options['options'])) {
            foreach ($options['options'] as $key => $value) {
                $pdf->setOption($key, $value);
            }
        }

        return $pdf->output();
    }

    /**
     * Generate PDF using Spatie PDF
     */
    protected function generateWithSpatie(string $view, array $data, array $options): string
    {
        $pdf = SpatiePdf::view($view, $data);

        // Set paper size and orientation
        if (isset($options['paper'])) {
            $pdf->paperSize($options['paper']);
        }

        if (isset($options['orientation'])) {
            $pdf->orientation($options['orientation']);
        }

        // Set margins
        if (isset($options['margins'])) {
            $margins = $options['margins'];
            $pdf->margins(
                $margins['top'] ?? 0,
                $margins['right'] ?? 0,
                $margins['bottom'] ?? 0,
                $margins['left'] ?? 0
            );
        }

        return $pdf->string();
    }

    /**
     * Generate and save PDF
     */
    public function generateAndSave(string $view, array $data, string $filename, array $options = []): string
    {
        $content = $this->generate($view, $data, $options);
        
        $storage = $this->config('storage');
        $disk = $storage['disk'];
        $path = $storage['path'] . '/' . $filename;
        
        Storage::disk($disk)->put($path, $content);
        
        return $path;
    }

    /**
     * Generate invoice PDF
     */
    public function generateInvoice(array $invoiceData, array $options = []): string
    {
        $options['template'] = 'invoice';
        return $this->generate('pdf.invoice', $invoiceData, $options);
    }

    /**
     * Generate quote PDF
     */
    public function generateQuote(array $quoteData, array $options = []): string
    {
        $options['template'] = 'quote';
        return $this->generate('pdf.quote', $quoteData, $options);
    }

    /**
     * Generate report PDF
     */
    public function generateReport(array $reportData, array $options = []): string
    {
        $options['template'] = 'report';
        return $this->generate('pdf.report', $reportData, $options);
    }

    /**
     * Generate ticket PDF
     */
    public function generateTicket(array $ticketData, array $options = []): string
    {
        $options['template'] = 'ticket';
        return $this->generate('pdf.ticket', $ticketData, $options);
    }

    /**
     * Generate asset report PDF
     */
    public function generateAssetReport(array $assetData, array $options = []): string
    {
        $options['template'] = 'asset_report';
        return $this->generate('pdf.asset-report', $assetData, $options);
    }

    /**
     * Generate contract PDF from HTML content
     */
    public function generateContractPDF(string $content, $contract, array $options = []): string
    {
        $template = $options['template'] ?? 'contract';

        // Get template configuration or use defaults
        $templates = $this->config('templates');
        $templateConfig = $templates[$template] ?? [
            'paper' => 'a4',
            'orientation' => 'portrait',
            'margins' => [
                'top' => 20,
                'right' => 20,
                'bottom' => 20,
                'left' => 20
            ]
        ];
        
        // Merge options with template config
        $pdfOptions = array_merge($templateConfig, $options);

        return $this->generateContractWithDomPdf($content, $contract, $pdfOptions);
    }

    /**
     * Generate contract PDF using DomPDF from HTML content
     */
    protected function generateContractWithDomPdf(string $content, $contract, array $options): string
    {
        $pdf = DomPDF::loadHTML($content);

        // Set paper size and orientation
        $paper = $options['paper'] ?? 'A4';
        $orientation = $options['orientation'] ?? 'portrait';
        $pdf->setPaper($paper, $orientation);

        // Set DomPDF options for better HTML rendering
        $pdf->setOptions([
            'defaultFont' => 'Times-Roman',
            'isRemoteEnabled' => true,
            'isPhpEnabled' => false,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultMediaType' => 'print',
            'defaultPaperSize' => $paper,
            'defaultPaperOrientation' => $orientation,
        ]);

        // Apply custom options if provided
        if (isset($options['options'])) {
            foreach ($options['options'] as $key => $value) {
                $pdf->setOption($key, $value);
            }
        }

        // Generate filename and path (matching ContractGenerationService fallback behavior)
        $filename = "contract-{$contract->contract_number}-" . time() . ".pdf";
        $path = "contracts/{$contract->company_id}/{$filename}";
        
        // Generate PDF content
        $pdfContent = $pdf->output();
        
        // Debug: Check if PDF content was generated
        if (empty($pdfContent)) {
            throw new \Exception('PDF content is empty - generation failed');
        }
        
        Log::info('PDF content generated with formatting', [
            'content_size' => strlen($pdfContent),
            'path' => $path,
            'has_html' => strpos($content, '<html>') !== false,
            'has_styles' => strpos($content, '<style>') !== false
        ]);
        
        // Save PDF to default storage (S3) - directories created automatically
        Storage::put($path, $pdfContent);
        
        return $path;
    }

    /**
     * Generate filename based on configuration
     */
    public function generateFilename(string $type, $id = null): string
    {
        $storage = $this->config('storage');
        $format = $storage['filename_format'];
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        $filename = str_replace(['{type}', '{id}', '{timestamp}'], [
            $type,
            $id ?? Str::random(8),
            $timestamp
        ], $format);

        return $filename;
    }

    /**
     * Get PDF storage path
     */
    public function getStoragePath(string $filename): string
    {
        $storage = $this->config('storage');
        return $storage['path'] . '/' . $filename;
    }

    /**
     * Get PDF URL
     */
    public function getUrl(string $filename): string
    {
        $storage = $this->config('storage');
        $disk = $storage['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->url($path);
    }

    /**
     * Delete PDF file
     */
    public function delete(string $filename): bool
    {
        $storage = $this->config('storage');
        $disk = $storage['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Check if PDF file exists
     */
    public function exists(string $filename): bool
    {
        $storage = $this->config('storage');
        $disk = $storage['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get PDF file size
     */
    public function getSize(string $filename): int
    {
        $storage = $this->config('storage');
        $disk = $storage['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->size($path);
    }

    /**
     * Stream PDF for download
     */
    public function download(string $view, array $data, string $filename, array $options = [])
    {
        $driver = $options['driver'] ?? $this->config('default');
        
        switch ($driver) {
            case 'spatie':
                return $this->downloadWithSpatie($view, $data, $filename, $options);
            case 'dompdf':
            default:
                return $this->downloadWithDomPdf($view, $data, $filename, $options);
        }
    }

    /**
     * Download PDF using DomPDF
     */
    protected function downloadWithDomPdf(string $view, array $data, string $filename, array $options)
    {
        $pdf = DomPDF::loadView($view, $data);
        
        if (isset($options['paper'])) {
            $pdf->setPaper($options['paper'], $options['orientation'] ?? 'portrait');
        }
        
        return $pdf->download($filename);
    }

    /**
     * Download PDF using Spatie PDF
     */
    protected function downloadWithSpatie(string $view, array $data, string $filename, array $options)
    {
        $pdf = SpatiePdf::view($view, $data);
        
        if (isset($options['paper'])) {
            $pdf->paperSize($options['paper']);
        }
        
        if (isset($options['orientation'])) {
            $pdf->orientation($options['orientation']);
        }
        
        return $pdf->download($filename);
    }

    /**
     * Get available templates
     */
    public function getTemplates(): array
    {
        $templates = $this->config('templates');
        return array_keys($templates);
    }

    /**
     * Get template configuration
     */
    public function getTemplateConfig(string $template): array
    {
        $templates = $this->config('templates');
        return $templates[$template] ?? [];
    }
}