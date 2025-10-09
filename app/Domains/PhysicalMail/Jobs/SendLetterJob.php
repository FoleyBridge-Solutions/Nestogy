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

        $payload = array_merge($payload, $this->getContentPayload());
        $payload = array_merge($payload, $this->getOptionalFieldsPayload());

        return $payload;
    }

    private function getContentPayload(): array
    {
        if ($this->mailable->template_id && $this->mailable->template) {
            return $this->getTemplatePayload();
        }

        if ($this->mailable->content) {
            return $this->getDirectContentPayload();
        }

        return [];
    }

    private function getTemplatePayload(): array
    {
        if ($this->mailable->template->postgrid_id) {
            return $this->getPostGridTemplatePayload();
        }

        return $this->getHtmlTemplatePayload();
    }

    private function getPostGridTemplatePayload(): array
    {
        $payload = ['template' => $this->mailable->template->postgrid_id];

        if (! empty($this->mailable->merge_variables)) {
            $payload['mergeVariables'] = $this->mailable->merge_variables;
        }

        return $payload;
    }

    private function getHtmlTemplatePayload(): array
    {
        $html = $this->mailable->template->content;

        if (! empty($this->mailable->merge_variables)) {
            $html = $this->replaceMergeVariables($html, $this->mailable->merge_variables);
        }

        return ['html' => $html];
    }

    private function getDirectContentPayload(): array
    {
        if (filter_var($this->mailable->content, FILTER_VALIDATE_URL)) {
            return ['pdf' => $this->mailable->content];
        }

        $html = $this->mailable->content;

        if (! empty($this->mailable->merge_variables)) {
            $html = $this->replaceMergeVariables($html, $this->mailable->merge_variables);
        }

        return ['html' => $html];
    }

    private function getOptionalFieldsPayload(): array
    {
        $payload = [];

        if ($this->mailable->perforated_page) {
            $payload['perforatedPage'] = $this->mailable->perforated_page;
        }

        if ($this->mailable->extra_service) {
            $payload['extraService'] = $this->mailable->extra_service;
        }

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
        $html = str_replace('{{/if}}', '', $html);

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
