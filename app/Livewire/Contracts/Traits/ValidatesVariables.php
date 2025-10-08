<?php

namespace App\Livewire\Contracts\Traits;

trait ValidatesVariables
{
    public function validateVariables()
    {
        $errors = [];
        $warnings = [];
        
        foreach ($this->variables as $key => $value) {
            if (empty($value) && $this->isVariableRequired($key)) {
                $errors[] = "Variable '{$key}' is required but has no value.";
            }
        }
        
        $content = $this->previewContent ?: $this->content;
        foreach ($this->variables as $key => $value) {
            if (strpos($content, '{{'.$key.'}}') === false) {
                $warnings[] = "Variable '{$key}' is defined but not used in the contract.";
            }
        }
        
        preg_match_all('/\{\{([^}]+)\}\}/', $content, $matches);
        foreach ($matches[1] as $match) {
            $varName = trim($match);
            if (strpos($varName, '#') === 0 || strpos($varName, '/') === 0 || strpos($varName, 'section') === 0) {
                continue;
            }
            if (!isset($this->variables[$varName])) {
                $errors[] = "Variable '{$varName}' is used but not defined.";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
    
    protected function isVariableRequired($key)
    {
        $required = ['client_name', 'contract_date', 'service_provider_name'];
        return in_array($key, $required);
    }
}
