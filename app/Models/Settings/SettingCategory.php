<?php

namespace App\Models\Settings;

use Illuminate\Database\Eloquent\Model;

abstract class SettingCategory
{
    protected Model $model;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    abstract public function getCategory(): string;

    abstract public function getAttributes(): array;

    protected function get(string $key, mixed $default = null): mixed
    {
        return $this->model->getAttribute($key) ?? $default;
    }

    protected function set(string $key, mixed $value): void
    {
        $this->model->setAttribute($key, $value);
    }

    protected function fillMany(array $data): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $this->getAttributes())) {
                $this->set($key, $value);
            }
        }
    }

    public function toArray(): array
    {
        $result = [];
        foreach ($this->getAttributes() as $attribute) {
            $result[$attribute] = $this->get($attribute);
        }
        return $result;
    }
}
