<?php

namespace App\Domains\Contract\Services\Generation;

use App\Domains\Contract\Models\Contract;
use App\Domains\Core\Services\TemplateVariableMapper;
use Illuminate\Support\Facades\Log;

class ContractDocumentBuilder
{
    protected TemplateVariableMapper $variableMapper;

    public function __construct(?TemplateVariableMapper $variableMapper = null)
    {
        $this->variableMapper = $variableMapper ?: new TemplateVariableMapper();
    }

    public function generate(Contract $contract): string
    {
        try {
            Log::info('Building contract document', ['contract_id' => $contract->id]);

            $content = $this->getTemplateContent($contract);
            $content = $this->processTemplateVariables($content, $contract);
            $content = $this->appendSchedules($content, $contract);
            $htmlContent = $this->convertMarkdownToHtml($content);

            $contract->update(['document_content' => $htmlContent]);

            Log::info('Contract document built successfully', ['contract_id' => $contract->id]);

            return $htmlContent;
        } catch (\Exception $e) {
            Log::error('Failed to build contract document', [
                'contract_id' => $contract->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    protected function getTemplateContent(Contract $contract): string
    {
        if ($contract->template && $contract->template->content) {
            return $contract->template->content;
        }

        return $this->generateDefaultTemplate($contract);
    }

    protected function generateDefaultTemplate(Contract $contract): string
    {
        $terms = $contract->terms ?? 'No specific terms defined.';
        $conditions = $contract->conditions ?? 'No specific conditions defined.';

        return <<<TEMPLATE
# Contract: {$contract->title}

**Client:** {$contract->client->name}
**Date:** {$contract->created_at->format('F d, Y')}

## Overview
{$contract->description}

## Terms and Conditions
{$terms}

## Conditions
{$conditions}

TEMPLATE;
    }

    protected function processTemplateVariables(string $content, Contract $contract): string
    {
        $variables = $this->variableMapper->generateVariables($contract);

        foreach ($variables as $key => $value) {
            $placeholder = '{' . $key . '}';
            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    protected function appendSchedules(string $content, Contract $contract): string
    {
        $schedules = $contract->schedules;

        if ($schedules->isEmpty()) {
            return $content;
        }

        $content .= "\n\n## Schedules\n";

        foreach ($schedules as $schedule) {
            $content .= $this->formatSchedule($schedule);
        }

        return $content;
    }

    protected function formatSchedule($schedule): string
    {
        $type = $schedule->type ?? 'general';

        return match ($type) {
            'pricing' => $this->formatPricingSchedule($schedule),
            'infrastructure' => $this->formatInfrastructureSchedule($schedule),
            'terms' => $this->formatTermsSchedule($schedule),
            default => $this->formatGenericSchedule($schedule),
        };
    }

    protected function formatPricingSchedule($schedule): string
    {
        return "\n### Pricing Schedule\n{$schedule->content}\n";
    }

    protected function formatInfrastructureSchedule($schedule): string
    {
        return "\n### Infrastructure Schedule\n{$schedule->content}\n";
    }

    protected function formatTermsSchedule($schedule): string
    {
        return "\n### Terms Schedule\n{$schedule->content}\n";
    }

    protected function formatGenericSchedule($schedule): string
    {
        $title = $schedule->title ?? 'Schedule';
        return "\n### {$title}\n{$schedule->content}\n";
    }

    protected function convertMarkdownToHtml(string $markdown): string
    {
        // Simple markdown to HTML conversion
        $html = $markdown;

        // Convert headers
        $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);

        // Convert bold
        $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);

        // Convert italics
        $html = preg_replace('/\*(.+?)\*/', '<em>$1</em>', $html);

        // Convert line breaks
        $html = nl2br($html);

        return "<html><body>{$html}</body></html>";
    }
}
