<?php

namespace App\Domains\PhysicalMail\Services;

class PhysicalMailTemplateBuilder
{
    // Standard measurements in pixels (at 96 DPI for HTML)
    const DPI = 96;

    const INCH_TO_PX = 96;

    // PostGrid address zones (in inches)
    const ADDRESS_ZONE_HEIGHT = 3.5;  // Top 3.5 inches reserved for addresses

    const RETURN_ADDRESS_TOP = 0.5;

    const RETURN_ADDRESS_LEFT = 0.5;

    const RECIPIENT_ADDRESS_TOP = 2.0;

    const RECIPIENT_ADDRESS_LEFT = 3.75;

    // Standard margins
    const MARGIN_TOP_SAFE = 4.0;  // Start content at 4 inches from top

    const MARGIN_SIDE = 0.75;     // 0.75 inch side margins

    const MARGIN_BOTTOM = 0.75;

    // Template types
    const TYPE_LETTER = 'letter';

    const TYPE_INVOICE = 'invoice';

    const TYPE_STATEMENT = 'statement';

    const TYPE_NOTICE = 'notice';

    const TYPE_MARKETING = 'marketing';

    /**
     * Build a safe HTML template with proper address zones
     */
    public function buildTemplate(
        string $content,
        string $type = self::TYPE_LETTER,
        array $options = []
    ): string {
        $styles = $this->getBaseStyles($type, $options);
        $headerContent = $this->getHeaderContent($type, $options);
        $wrappedContent = $this->wrapContent($content, $type);

        return $this->assembleTemplate($styles, $headerContent, $wrappedContent, $options);
    }

    /**
     * Get base CSS styles for the template
     */
    private function getBaseStyles(string $type, array $options): string
    {
        $topMargin = ($options['top_margin'] ?? self::MARGIN_TOP_SAFE) * self::INCH_TO_PX;
        $sideMargin = self::MARGIN_SIDE * self::INCH_TO_PX;
        $primaryColor = $options['primary_color'] ?? '#1a56db';

        return "
        <style>
            @page {
                size: letter;
                margin: 0;
            }
            
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif;
                font-size: 11pt;
                line-height: 1.6;
                color: #111827;
                background: white;
                margin: 0;
                padding: 0;
            }
            
            /* Address zone placeholder - keeps content below */
            .address-zone {
                height: {$topMargin}px;
                position: relative;
                page-break-after: avoid;
            }
            
            /* Optional company logo in safe area */
            .letterhead {
                position: absolute;
                top: 20px;
                right: {$sideMargin}px;
                max-width: 150px;
                max-height: 60px;
            }
            
            .letterhead img {
                max-width: 100%;
                height: auto;
            }
            
            /* Main content area */
            .content-wrapper {
                padding: 0 {$sideMargin}px;
                padding-bottom: ".(self::MARGIN_BOTTOM * self::INCH_TO_PX)."px;
                min-height: calc(11in - {$topMargin}px - ".(self::MARGIN_BOTTOM * self::INCH_TO_PX)."px);
            }
            
            /* Typography */
            h1 {
                color: {$primaryColor};
                font-size: 18pt;
                margin-bottom: 12pt;
                font-weight: 600;
            }
            
            h2 {
                color: #374151;
                font-size: 14pt;
                margin-top: 18pt;
                margin-bottom: 8pt;
                font-weight: 600;
            }
            
            h3 {
                color: #4b5563;
                font-size: 12pt;
                margin-top: 12pt;
                margin-bottom: 6pt;
                font-weight: 600;
            }
            
            p {
                margin-bottom: 10pt;
                text-align: justify;
            }
            
            ul, ol {
                margin-left: 20pt;
                margin-bottom: 10pt;
            }
            
            li {
                margin-bottom: 4pt;
            }
            
            /* Tables */
            table {
                width: 100%;
                border-collapse: collapse;
                margin: 12pt 0;
            }
            
            th {
                background-color: #f3f4f6;
                padding: 8pt;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #d1d5db;
            }
            
            td {
                padding: 8pt;
                border-bottom: 1px solid #e5e7eb;
            }
            
            /* Signature block */
            .signature {
                margin-top: 30pt;
            }
            
            .signature-name {
                font-weight: 600;
                margin-top: 4pt;
            }
            
            .signature-title {
                color: #6b7280;
                font-size: 10pt;
            }
            
            /* Footer */
            .footer {
                margin-top: 30pt;
                padding-top: 12pt;
                border-top: 1px solid #e5e7eb;
                font-size: 9pt;
                color: #6b7280;
            }
            
            /* Invoice specific */
            .invoice-header {
                display: flex;
                justify-content: space-between;
                margin-bottom: 20pt;
            }
            
            .invoice-details {
                text-align: right;
            }
            
            .invoice-number {
                font-size: 14pt;
                font-weight: bold;
                color: {$primaryColor};
            }
            
            .amount-due {
                font-size: 16pt;
                font-weight: bold;
                color: #dc2626;
                margin-top: 10pt;
            }
            
            /* Utility classes */
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-muted { color: #6b7280; }
            .mt-1 { margin-top: 8pt; }
            .mt-2 { margin-top: 16pt; }
            .mt-3 { margin-top: 24pt; }
            .mb-1 { margin-bottom: 8pt; }
            .mb-2 { margin-bottom: 16pt; }
            .mb-3 { margin-bottom: 24pt; }
            
            /* Page break handling */
            .page-break { page-break-after: always; }
            .avoid-break { page-break-inside: avoid; }
        </style>
        ";
    }

    /**
     * Get header content for the safe zone
     */
    private function getHeaderContent(string $type, array $options): string
    {
        $logoUrl = $options['logo_url'] ?? '';
        $headerHtml = '<div class="address-zone">';

        if ($logoUrl) {
            $headerHtml .= '
                <div class="letterhead">
                    <img src="'.htmlspecialchars($logoUrl).'" alt="Logo">
                </div>
            ';
        }

        // Add any watermark or background elements that won't interfere with addresses
        if (isset($options['watermark'])) {
            $headerHtml .= '
                <div style="position: absolute; top: 50px; right: 20px; opacity: 0.1; transform: rotate(-45deg); font-size: 48pt; color: #e5e7eb;">
                    '.htmlspecialchars($options['watermark']).'
                </div>
            ';
        }

        $headerHtml .= '</div>';

        return $headerHtml;
    }

    /**
     * Wrap content based on template type
     */
    private function wrapContent(string $content, string $type): string
    {
        return '<div class="content-wrapper">'.$content.'</div>';
    }

    /**
     * Assemble the complete template
     */
    private function assembleTemplate(
        string $styles,
        string $header,
        string $content,
        array $options
    ): string {
        $title = $options['title'] ?? 'Document';

        return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>'.htmlspecialchars($title).'</title>
    '.$styles.'
</head>
<body>
    '.$header.'
    '.$content.'
</body>
</html>';
    }

    /**
     * Check if content has elements in the address zone
     */
    public function hasContentInAddressZone(string $html): bool
    {
        // Parse HTML to check for content positioning
        $dom = new \DOMDocument;
        @$dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        // Check for absolute positioned elements in address zone
        $xpath = new \DOMXPath($dom);
        $elements = $xpath->query('//*[@style]');

        foreach ($elements as $element) {
            $style = $element->getAttribute('style');
            if (preg_match('/position:\s*absolute/i', $style)) {
                if (preg_match('/top:\s*(\d+)(px|pt|in|cm)/i', $style, $matches)) {
                    $value = (float) $matches[1];
                    $unit = $matches[2];

                    // Convert to inches
                    $inches = $this->convertToInches($value, $unit);

                    if ($inches < self::ADDRESS_ZONE_HEIGHT) {
                        return true; // Content in address zone
                    }
                }
            }
        }

        // Check if body has enough top margin/padding
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            $style = $body->getAttribute('style');
            if (! preg_match('/margin-top:\s*(\d+)/i', $style) &&
                ! preg_match('/padding-top:\s*(\d+)/i', $style)) {
                // No top margin/padding, content likely starts at top
                return true;
            }
        }

        return false;
    }

    /**
     * Convert measurement to inches
     */
    private function convertToInches(float $value, string $unit): float
    {
        return match ($unit) {
            'in' => $value,
            'px' => $value / self::DPI,
            'pt' => $value / 72,
            'cm' => $value / 2.54,
            default => $value / self::DPI,
        };
    }

    /**
     * Generate a standard business letter template
     */
    public function generateBusinessLetter(array $data): string
    {
        $content = '
        <p class="date">{{date}}</p>
        
        <div class="recipient mb-3">
            <p><strong>{{to.firstName}} {{to.lastName}}</strong></p>
            <p>{{to.companyName}}</p>
            <p>{{to.addressLine1}}</p>
            {{#if to.addressLine2}}<p>{{to.addressLine2}}</p>{{/if}}
            <p>{{to.city}}, {{to.provinceOrState}} {{to.postalOrZip}}</p>
        </div>
        
        <p>Dear {{to.firstName}} {{to.lastName}},</p>
        
        '.($data['body'] ?? '<p>{{body}}</p>').'
        
        <div class="signature">
            <p>Sincerely,</p>
            <p class="signature-name">{{from.firstName}} {{from.lastName}}</p>
            <p class="signature-title">{{from.jobTitle}}</p>
            <p class="text-muted">{{from.companyName}}</p>
        </div>
        ';

        return $this->buildTemplate($content, self::TYPE_LETTER, $data);
    }

    /**
     * Generate an invoice template
     */
    public function generateInvoiceTemplate(array $data): string
    {
        $content = '
        <div class="invoice-header">
            <div>
                <h1>INVOICE</h1>
                <div class="recipient">
                    <p><strong>{{to.companyName}}</strong></p>
                    <p>{{to.firstName}} {{to.lastName}}</p>
                    <p>{{to.addressLine1}}</p>
                    {{#if to.addressLine2}}<p>{{to.addressLine2}}</p>{{/if}}
                    <p>{{to.city}}, {{to.provinceOrState}} {{to.postalOrZip}}</p>
                </div>
            </div>
            <div class="invoice-details">
                <p class="invoice-number">Invoice #{{invoice_number}}</p>
                <p>Date: {{invoice_date}}</p>
                <p>Due Date: {{due_date}}</p>
                <p class="amount-due">Amount Due: {{amount_due}}</p>
            </div>
        </div>
        
        <table class="mt-3">
            <thead>
                <tr>
                    <th>Description</th>
                    <th style="text-align: right;">Amount</th>
                </tr>
            </thead>
            <tbody>
                {{#each line_items}}
                <tr>
                    <td>{{description}}</td>
                    <td style="text-align: right;">{{amount}}</td>
                </tr>
                {{/each}}
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th style="text-align: right;">{{total_amount}}</th>
                </tr>
            </tfoot>
        </table>
        
        <div class="mt-3">
            <h3>Payment Instructions</h3>
            <p>{{payment_instructions}}</p>
        </div>
        
        <div class="footer">
            <p class="text-center text-muted">Thank you for your business!</p>
            <p class="text-center text-muted">{{from.companyName}} | {{from.email}} | {{from.phoneNumber}}</p>
        </div>
        ';

        return $this->buildTemplate($content, self::TYPE_INVOICE, $data);
    }
}
