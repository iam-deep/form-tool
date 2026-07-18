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
        array $messages = []
    ): static {
        $fields = $this->normalizeVisibilityFields($fields);
        $values = $this->normalizeVisibilityValues($values);
        $messages = $this->normalizeVisibilityMessages($messages, $fields);

        foreach ($fields as $field) {
            $existing = $this->visibilityRules[$field] ?? null;
            if ($existing && $existing['action'] !== $action) {
                throw new FormToolException(
                    'Field "'.$field.'" cannot have both show and hide visibility rules.'
                );
            }

            $existingMessage = $existing['message'] ?? null;
            $newMessage = $messages[$field] ?? null;

            if ($existingMessage !== null && $newMessage !== null && $existingMessage !== $newMessage) {
                throw new FormToolException(
                    'Visibility message for field "'.$field.'" conflicts with an existing message.'
                );
            }

            $rule = [
                'action' => $action,
                'values' => array_values(array_unique(array_merge($existing['values'] ?? [], $values))),
            ];

            if (($newMessage ?? $existingMessage) !== null) {
                $rule['message'] = $newMessage ?? $existingMessage;
            }

            $this->visibilityRules[$field] = $rule;
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

    private function normalizeVisibilityMessages(array $messages, array $fields): array
    {
        $normalized = [];

        foreach ($messages as $field => $message) {
            $field = is_string($field) ? trim($field) : '';

            if (! in_array($field, $fields, true)) {
                throw new FormToolException(
                    'Visibility message field "'.$field.'" must be included in the target fields.'
                );
            }

            if (! is_string($message) || trim($message) === '') {
                throw new FormToolException(
                    'Visibility message for field "'.$field.'" cannot be empty.'
                );
            }

            $normalized[$field] = $message;
        }

        return $normalized;
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
