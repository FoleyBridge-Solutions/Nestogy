<?php

namespace App\Domains\PhysicalMail\Services;

use App\Domains\PhysicalMail\Models\PhysicalMailTemplate;

class PhysicalMailTemplateService
{
    public function __construct(private PostGridClient $postgrid)
    {
        // Dependencies are injected
    }

    /**
     * Find or create a template
     */
    public function findOrCreate(array $data): PhysicalMailTemplate
    {
        // Try to find existing template by name
        if (! empty($data['name'])) {
            $template = PhysicalMailTemplate::where('name', $data['name'])->first();
            if ($template) {
                return $template;
            }
        }

        return $this->create($data);
    }

    /**
     * Create a new template
     */
    public function create(array $data): PhysicalMailTemplate
    {
        // Extract variables from content if HTML is provided
        if (! empty($data['content']) && empty($data['variables'])) {
            $data['variables'] = $this->extractVariables($data['content']);
        }

        // Create local template
        $template = PhysicalMailTemplate::create($data);

        // Sync with PostGrid if content is provided
        if (! $template->postgrid_id && $template->content) {
            try {
                $response = $this->postgrid->createTemplate($template->toPostGridArray());
                $template->update(['postgrid_id' => $response['id']]);
            } catch (\Exception $e) {
                \Log::warning('Failed to sync template with PostGrid', [
                    'template_id' => $template->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $template;
    }

    /**
     * Sync template from PostGrid
     */
    public function syncFromPostGrid(string $postgridId): PhysicalMailTemplate
    {
        $response = $this->postgrid->getTemplate($postgridId);

        return PhysicalMailTemplate::updateOrCreate(
            ['postgrid_id' => $response['id']],
            [
                'name' => $response['name'],
                'type' => $this->mapPostGridType($response['type'] ?? 'letter'),
                'content' => $response['html'] ?? null,
                'description' => $response['description'] ?? null,
                'variables' => $this->extractVariables($response['html'] ?? ''),
                'metadata' => $response['metadata'] ?? [],
                'is_active' => true,
            ]
        );
    }

    /**
     * Update template
     */
    public function update(\Illuminate\Database\Eloquent\Model $model, array $data): \Illuminate\Database\Eloquent\Model
    {
        // Update variables if content changed
        if (isset($data['content']) && $data['content'] !== $model->content) {
            $data['variables'] = $this->extractVariables($data['content']);
        }

        $model->update($data);

        // Note: PostGrid doesn't have template update endpoint
        // Would need to create new template and update postgrid_id

        return $model;
    }

    /**
     * Extract merge variables from HTML content
     */
    private function extractVariables(string $content): array
    {
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);

        return array_unique($matches[1] ?? []);
    }

    /**
     * Map PostGrid template type to our type
     */
    private function mapPostGridType(string $type): string
    {
        return match (strtolower($type)) {
            'letter' => 'letter',
            'postcard' => 'postcard',
            'cheque', 'check' => 'cheque',
            'selfmailer', 'self_mailer' => 'self_mailer',
            default => 'letter',
        };
    }

    /**
     * Get active templates by type
     */
    public function getActiveByType(string $type): \Illuminate\Database\Eloquent\Collection
    {
        return PhysicalMailTemplate::active()
            ->byType($type)
            ->orderBy('name')
            ->get();
    }

    /**
     * Validate merge variables
     */
    public function validateMergeVariables(PhysicalMailTemplate $template, array $variables): array
    {
        $missing = [];
        $templateVars = $template->variables ?? [];

        foreach ($templateVars as $var) {
            // Skip system variables (to.*, from.*)
            if (str_starts_with($var, 'to.') || str_starts_with($var, 'from.')) {
                continue;
            }

            // Check if variable is provided
            $parts = explode('.', $var);
            $value = $variables;

            foreach ($parts as $part) {
                if (! isset($value[$part])) {
                    $missing[] = $var;
                    break;
                }
                $value = $value[$part];
            }
        }

        return $missing;
    }

    /**
     * Preview template with merge variables
     */
    public function preview(PhysicalMailTemplate $template, array $variables = []): string
    {
        $content = $template->content;

        // Replace variables
        foreach ($variables as $key => $value) {
            if (is_array($value)) {
                // Handle nested variables
                foreach ($value as $subKey => $subValue) {
                    $content = str_replace("{{{$key}.{$subKey}}}", $subValue, $content);
                }
            } else {
                $content = str_replace("{{{$key}}}", $value, $content);
            }
        }

        return $content;
    }
}
