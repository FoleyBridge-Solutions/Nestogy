<?php

namespace App\Domains\PhysicalMail\Jobs;

use App\Domains\PhysicalMail\Models\PhysicalMailLetter;

class SendLetterJob extends BasePhysicalMailJob
{
    /**
     * Create a new job instance
     */
    public function __construct(PhysicalMailLetter $letter)
    {
        parent::__construct();
        $this->mailable = $letter;
    }
    
    /**
     * Get the mail type
     */
    protected function getMailType(): string
    {
        return 'letters';
    }
    
    /**
     * Get letter-specific payload
     */
    protected function getTypeSpecificPayload(): array
    {
        $payload = [
            'color' => $this->mailable->color,
            'doubleSided' => $this->mailable->double_sided,
            'addressPlacement' => $this->mailable->address_placement,
            'size' => $this->mailable->size,
        ];
        
        // Add content (template, HTML, or PDF)
        if ($this->mailable->template_id && $this->mailable->template) {
            if ($this->mailable->template->postgrid_id) {
                $payload['template'] = $this->mailable->template->postgrid_id;
                // Only add merge variables when using PostGrid templates
                if (!empty($this->mailable->merge_variables)) {
                    $payload['mergeVariables'] = $this->mailable->merge_variables;
                }
            } else {
                // Process HTML to replace merge variables
                $html = $this->mailable->template->content;
                if (!empty($this->mailable->merge_variables)) {
                    $html = $this->replaceMergeVariables($html, $this->mailable->merge_variables);
                }
                $payload['html'] = $html;
            }
        } elseif ($this->mailable->content) {
            // Determine if content is HTML or PDF URL
            if (filter_var($this->mailable->content, FILTER_VALIDATE_URL)) {
                $payload['pdf'] = $this->mailable->content;
            } else {
                // Process HTML to replace merge variables
                $html = $this->mailable->content;
                if (!empty($this->mailable->merge_variables)) {
                    $html = $this->replaceMergeVariables($html, $this->mailable->merge_variables);
                }
                $payload['html'] = $html;
            }
        }
        
        // Add perforation
        if ($this->mailable->perforated_page) {
            $payload['perforatedPage'] = $this->mailable->perforated_page;
        }
        
        // Add extra service (certified, registered, etc.)
        if ($this->mailable->extra_service) {
            $payload['extraService'] = $this->mailable->extra_service;
        }
        
        // Add return envelope
        if ($this->mailable->return_envelope_id && $this->mailable->returnEnvelope) {
            if ($this->mailable->returnEnvelope->postgrid_id) {
                $payload['returnEnvelope'] = $this->mailable->returnEnvelope->postgrid_id;
            }
        }
        
        return $payload;
    }
    
    /**
     * Replace merge variables in HTML content
     */
    private function replaceMergeVariables(string $html, array $variables): string
    {
        // First handle Handlebars conditionals (remove them for now)
        $html = preg_replace('/\{\{#if[^}]+\}\}/', '', $html);
        $html = preg_replace('/\{\{\/if\}\}/', '', $html);
        
        // Replace nested variables (e.g., {{to.firstName}})
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $html = str_replace('{{' . $key . '.' . $subKey . '}}', $subValue ?? '', $html);
                }
            } else {
                $html = str_replace('{{' . $key . '}}', $value ?? '', $html);
            }
        }
        
        // Clean up any remaining unmatched variables
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);
        
        return $html;
    }
}