<?php

namespace App\Domains\Core\Services;

/**
 * Parsed Command Value Object
 *
 * Represents a structured command after intent parsing.
 * Immutable data structure containing all command components.
 */
class ParsedCommand
{
    protected array $data;

    public function __construct(array $data)
    {
        $this->data = array_merge([
            'original' => '',
            'intent' => '',
            'entities' => [],
            'modifiers' => [],
            'entity_reference' => null,
            'search_query' => null,
            'context' => [],
            'confidence' => 0.0,
            'is_shortcut' => false,
        ], $data);
    }

    public function getOriginal(): string
    {
        return $this->data['original'];
    }

    public function getIntent(): string
    {
        return $this->data['intent'];
    }

    public function getEntities(): array
    {
        return $this->data['entities'];
    }

    public function getPrimaryEntity(): ?string
    {
        return $this->data['entities'][0] ?? null;
    }

    public function hasEntity(string $entity): bool
    {
        return in_array($entity, $this->data['entities']);
    }

    public function getModifiers(): array
    {
        return $this->data['modifiers'];
    }

    public function hasModifier(string $modifier): bool
    {
        return in_array($modifier, $this->data['modifiers']);
    }

    public function getEntityReference(): ?array
    {
        return $this->data['entity_reference'];
    }

    public function hasEntityReference(): bool
    {
        return $this->data['entity_reference'] !== null;
    }

    public function getSearchQuery(): ?string
    {
        return $this->data['search_query'];
    }

    public function getContext(): array
    {
        return $this->data['context'];
    }

    public function getContextValue(string $key, $default = null)
    {
        return $this->data['context'][$key] ?? $default;
    }

    public function hasContext(string $key): bool
    {
        return isset($this->data['context'][$key]);
    }

    public function getConfidence(): float
    {
        return $this->data['confidence'];
    }

    public function isShortcut(): bool
    {
        return $this->data['is_shortcut'];
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return "ParsedCommand({$this->getIntent()}: ".implode(',', $this->getEntities()).')';
    }
}
