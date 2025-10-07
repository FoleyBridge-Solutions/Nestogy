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

        $this->addContentToPayload($payload);
        $this->addOptionalFieldsToPayload($payload);

        return $payload;
    }

    /**
     * Add content (template, HTML, or PDF) to payload
     */
    private function addContentToPayload(array &$payload): void
    {
        if ($this->mailable->template_id && $this->mailable->template) {
            $this->addTemplateContent($payload);
            return;
        }

        if ($this->mailable->content) {
            $this->addDirectContent($payload);
        }
    }

    /**
     * Add template-based content to payload
     */
    private function addTemplateContent(array &$payload): void
    {
        if ($this->mailable->template->postgrid_id) {
            $payload['template'] = $this->mailable->template->postgrid_id;
            if (! empty($this->mailable->merge_variables)) {
                $payload['mergeVariables'] = $this->mailable->merge_variables;
            }
            return;
        }

        $html = $this->mailable->template->content;
        $payload['html'] = $this->processHtmlWithMergeVariables($html);
    }

    /**
     * Add direct content (HTML or PDF) to payload
     */
    private function addDirectContent(array &$payload): void
    {
        if (filter_var($this->mailable->content, FILTER_VALIDATE_URL)) {
            $payload['pdf'] = $this->mailable->content;
            return;
        }

        $payload['html'] = $this->processHtmlWithMergeVariables($this->mailable->content);
    }

    /**
     * Process HTML content with merge variables
     */
    private function processHtmlWithMergeVariables(string $html): string
    {
        if (empty($this->mailable->merge_variables)) {
            return $html;
        }

        return $this->replaceMergeVariables($html, $this->mailable->merge_variables);
    }

    /**
     * Add optional fields to payload
     */
    private function addOptionalFieldsToPayload(array &$payload): void
    {
        if ($this->mailable->perforated_page) {
            $payload['perforatedPage'] = $this->mailable->perforated_page;
        }

        if ($this->mailable->extra_service) {
            $payload['extraService'] = $this->mailable->extra_service;
        }

        if ($this->mailable->return_envelope_id && $this->mailable->returnEnvelope?->postgrid_id) {
            $payload['returnEnvelope'] = $this->mailable->returnEnvelope->postgrid_id;
        }
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
                    $html = str_replace('{{'.$key.'.'.$subKey.'}}', $subValue ?? '', $html);
                }
            } else {
                $html = str_replace('{{'.$key.'}}', $value ?? '', $html);
            }
        }

        // Clean up any remaining unmatched variables
        $html = preg_replace('/\{\{[^}]+\}\}/', '', $html);

        return $html;
    }
}
