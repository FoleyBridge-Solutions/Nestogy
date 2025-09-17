<?php

namespace App\Domains\PhysicalMail\Services;

class PhysicalMailContentAnalyzer
{
    private PhysicalMailTemplateBuilder $templateBuilder;
    
    public function __construct(PhysicalMailTemplateBuilder $templateBuilder)
    {
        $this->templateBuilder = $templateBuilder;
    }
    
    /**
     * Analyze content and determine best address placement strategy
     */
    public function analyzeContent(string $content, string $type = 'html'): array
    {
        $analysis = [
            'has_address_conflict' => false,
            'recommended_placement' => 'top_first_page',
            'needs_reformatting' => false,
            'estimated_pages' => 1,
            'issues' => [],
            'suggestions' => [],
        ];
        
        if ($type === 'html') {
            $analysis = $this->analyzeHtml($content, $analysis);
        } elseif ($type === 'pdf') {
            $analysis = $this->analyzePdf($content, $analysis);
        }
        
        return $analysis;
    }
    
    /**
     * Analyze HTML content
     */
    private function analyzeHtml(string $html, array $analysis): array
    {
        // Check for content in address zone
        if ($this->templateBuilder->hasContentInAddressZone($html)) {
            $analysis['has_address_conflict'] = true;
            $analysis['issues'][] = 'Content detected in address zone (top 3.5 inches)';
            $analysis['suggestions'][] = 'Add margin-top of at least 336px (3.5 inches)';
        }
        
        // Check for proper DOCTYPE and structure
        if (!preg_match('/<(!DOCTYPE|html)/i', $html)) {
            $analysis['needs_reformatting'] = true;
            $analysis['issues'][] = 'Missing proper HTML structure';
            $analysis['suggestions'][] = 'Wrap content in proper HTML template';
        }
        
        // Check for margins
        if (!preg_match('/margin-top:\s*([3-9]\d{2}|[1-9]\d{3})/i', $html)) {
            $analysis['needs_reformatting'] = true;
            $analysis['suggestions'][] = 'Consider adding top margin for address zone';
        }
        
        // Estimate page count (rough estimate based on content length)
        $textContent = strip_tags($html);
        $charCount = strlen($textContent);
        $analysis['estimated_pages'] = max(1, ceil($charCount / 3000));
        
        // Determine recommended placement
        if ($analysis['has_address_conflict']) {
            if ($analysis['estimated_pages'] === 1) {
                // For single page with conflict, reformat is better
                $analysis['recommended_placement'] = 'reformat';
                $analysis['suggestions'][] = 'Reformat content with proper margins instead of adding blank page';
            } else {
                // For multi-page, insert blank might be acceptable
                $analysis['recommended_placement'] = 'insert_blank_page';
            }
        }
        
        return $analysis;
    }
    
    /**
     * Analyze PDF content (URL)
     */
    private function analyzePdf(string $pdfUrl, array $analysis): array
    {
        // For PDF URLs, we can't analyze content directly
        // Default to safe settings
        $analysis['recommended_placement'] = 'insert_blank_page';
        $analysis['suggestions'][] = 'Consider using HTML templates for better control';
        
        return $analysis;
    }
    
    /**
     * Fix HTML content to be PostGrid safe
     */
    public function makeContentSafe(string $content, string $type = 'letter'): string
    {
        // If content is already safe, return as-is
        if (!$this->templateBuilder->hasContentInAddressZone($content)) {
            return $content;
        }
        
        // Check if it's raw content without HTML structure
        if (!preg_match('/<html/i', $content)) {
            // It's raw content, wrap it in safe template
            return $this->templateBuilder->buildTemplate($content, $type);
        }
        
        // It's HTML but needs margin adjustment
        return $this->addSafeMargins($content);
    }
    
    /**
     * Add safe margins to existing HTML
     */
    private function addSafeMargins(string $html): string
    {
        // Add/update margin-top in body style
        if (preg_match('/<body([^>]*)>/i', $html, $matches)) {
            $bodyTag = $matches[0];
            $attributes = $matches[1];
            
            // Check if style attribute exists
            if (preg_match('/style=["\']([^"\']*)["\']/', $attributes, $styleMatch)) {
                $currentStyle = $styleMatch[1];
                // Remove any existing margin-top
                $currentStyle = preg_replace('/margin-top:\s*[^;]+;?/i', '', $currentStyle);
                // Add safe margin-top
                $newStyle = 'margin-top: 336px; ' . $currentStyle;
                $newAttributes = preg_replace('/style=["\'][^"\']*["\']/', 'style="' . $newStyle . '"', $attributes);
            } else {
                // Add style attribute with margin-top
                $newAttributes = $attributes . ' style="margin-top: 336px;"';
            }
            
            $newBodyTag = '<body' . $newAttributes . '>';
            $html = str_replace($bodyTag, $newBodyTag, $html);
        }
        
        // If no body tag, wrap content
        if (!preg_match('/<body/i', $html)) {
            $html = '<html><head><style>body { margin-top: 336px; }</style></head><body>' . $html . '</body></html>';
        }
        
        return $html;
    }
    
    /**
     * Determine if content should use insert_blank_page
     */
    public function shouldUseBlankPage(array $analysis): bool
    {
        // Use blank page only when absolutely necessary
        return $analysis['has_address_conflict'] && 
               $analysis['estimated_pages'] > 1 &&
               $analysis['recommended_placement'] === 'insert_blank_page';
    }
}