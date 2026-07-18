<?php

namespace Deep\FormTool\Core\InputTypes\Common;

use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Arr;

trait VisibilityRules
{
    protected array $visibilityRules = [];

    public function getVisibilityRules(): array
    {
        return $this->visibilityRules;
    }

    protected function addVisibilityRule(
        string $action,
        string|array $fields,
        mixed $values,
        bool $isRequiredOnShow
    ): static {
        $fields = $this->normalizeVisibilityFields($fields);
        $values = $this->normalizeVisibilityValues($values);

        foreach ($fields as $field) {
            $existing = $this->visibilityRules[$field] ?? null;
            if ($existing && $existing['action'] !== $action) {
                throw new FormToolException(
                    'Field "'.$field.'" cannot have both show and hide visibility rules.'
                );
            }

            $this->visibilityRules[$field] = [
                'action' => $action,
                'values' => array_values(array_unique(array_merge($existing['values'] ?? [], $values))),
                'isRequiredOnShow' => ($existing['isRequiredOnShow'] ?? false) || $isRequiredOnShow,
            ];
        }

        return $this;
    }

    private function normalizeVisibilityFields(string|array $fields): array
    {
        $normalized = [];

        foreach (Arr::wrap($fields) as $field) {
            if (! is_string($field) || trim($field) === '') {
                throw new FormToolException('Visibility fields cannot be empty.');
            }

            $normalized[] = trim($field);
        }

        if (! $normalized) {
            throw new FormToolException('Visibility fields cannot be empty.');
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeVisibilityValues(mixed $values): array
    {
        $normalized = [];

        foreach ($values === null ? [null] : Arr::wrap($values) as $value) {
            if (! is_scalar($value) && $value !== null) {
                throw new FormToolException('Visibility values must be scalar or null.');
            }

            $normalized[] = is_bool($value) ? ($value ? '1' : '0') : (string) $value;
        }

        if (! $normalized) {
            throw new FormToolException('Visibility values cannot be empty.');
        }

        return array_values(array_unique($normalized));
    }
}
