<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf as DomPDF;
use Spatie\LaravelPdf\Facades\Pdf as SpatiePdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PdfService
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Generate PDF from view
     */
    public function generate(string $view, array $data = [], array $options = []): string
    {
        $driver = $options['driver'] ?? $this->config['default'];
        $template = $options['template'] ?? 'default';

        // Get template configuration
        $templateConfig = $this->config['templates'][$template] ?? $this->config['templates']['default'];
        
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
        
        $disk = $this->config['storage']['disk'];
        $path = $this->config['storage']['path'] . '/' . $filename;
        
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
     * Generate filename based on configuration
     */
    public function generateFilename(string $type, $id = null): string
    {
        $format = $this->config['storage']['filename_format'];
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
        return $this->config['storage']['path'] . '/' . $filename;
    }

    /**
     * Get PDF URL
     */
    public function getUrl(string $filename): string
    {
        $disk = $this->config['storage']['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->url($path);
    }

    /**
     * Delete PDF file
     */
    public function delete(string $filename): bool
    {
        $disk = $this->config['storage']['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->delete($path);
    }

    /**
     * Check if PDF file exists
     */
    public function exists(string $filename): bool
    {
        $disk = $this->config['storage']['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->exists($path);
    }

    /**
     * Get PDF file size
     */
    public function getSize(string $filename): int
    {
        $disk = $this->config['storage']['disk'];
        $path = $this->getStoragePath($filename);
        
        return Storage::disk($disk)->size($path);
    }

    /**
     * Stream PDF for download
     */
    public function download(string $view, array $data, string $filename, array $options = [])
    {
        $driver = $options['driver'] ?? $this->config['default'];
        
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
        return array_keys($this->config['templates']);
    }

    /**
     * Get template configuration
     */
    public function getTemplateConfig(string $template): array
    {
        return $this->config['templates'][$template] ?? [];
    }
}