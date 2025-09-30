<?php

namespace App\Contracts\Services;

interface PdfServiceInterface
{
    /**
     * Generate PDF from view
     */
    public function generate(string $view, array $data = [], array $options = []): string;

    /**
     * Generate PDF and save to storage
     */
    public function generateAndSave(string $view, array $data, string $filename, array $options = []): string;

    /**
     * Generate invoice PDF
     */
    public function generateInvoice(array $invoiceData, array $options = []): string;

    /**
     * Generate quote PDF
     */
    public function generateQuote(array $quoteData, array $options = []): string;

    /**
     * Generate report PDF
     */
    public function generateReport(array $reportData, array $options = []): string;

    /**
     * Generate ticket PDF
     */
    public function generateTicket(array $ticketData, array $options = []): string;

    /**
     * Generate asset report PDF
     */
    public function generateAssetReport(array $assetData, array $options = []): string;

    /**
     * Generate contract PDF
     */
    public function generateContractPDF(string $content, $contract, array $options = []): string;

    /**
     * Generate filename for PDF
     */
    public function generateFilename(string $type, $id = null): string;

    /**
     * Get storage path for PDF
     */
    public function getStoragePath(string $filename): string;

    /**
     * Get URL for PDF
     */
    public function getUrl(string $filename): string;

    /**
     * Delete PDF file
     */
    public function delete(string $filename): bool;

    /**
     * Check if PDF file exists
     */
    public function exists(string $filename): bool;

    /**
     * Get PDF file size
     */
    public function getSize(string $filename): int;

    /**
     * Download PDF
     */
    public function download(string $view, array $data, string $filename, array $options = []);

    /**
     * Get available templates
     */
    public function getTemplates(): array;

    /**
     * Get template configuration
     */
    public function getTemplateConfig(string $template): array;
}
