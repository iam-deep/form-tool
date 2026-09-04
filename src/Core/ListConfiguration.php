<?php

namespace Deep\FormTool\Core;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ListConfiguration
{
    private array $columns;
    private array $filters;
    private array $selectedColumns;
    private array $selectedFilters;
    private bool $perPageEnabled;
    private array $perPageOptions;
    private int $defaultPerPage;
    private ?string $saveUrl;
    private bool $canUpdate;

    public function __construct(array $options)
    {
        $this->columns = $this->normalizeOptions($options['columns'] ?? []);
        $this->filters = $this->normalizeOptions($options['filters'] ?? []);

        $defaults = is_array($options['defaults'] ?? null) ? $options['defaults'] : [];
        $values = is_array($options['values'] ?? null) ? $options['values'] : [];

        $defaultColumns = $this->allowedSelection($defaults['columns'] ?? array_keys($this->columns), $this->columns);
        $this->selectedColumns = $this->allowedSelection($values['columns'] ?? $defaultColumns, $this->columns);
        if (! $this->selectedColumns) {
            $this->selectedColumns = $defaultColumns ?: array_keys($this->columns);
        }

        $defaultFilters = $this->allowedSelection($defaults['filters'] ?? array_keys($this->filters), $this->filters);
        $this->selectedFilters = array_key_exists('filters', $values)
            ? $this->allowedSelection($values['filters'], $this->filters)
            : $defaultFilters;

        $this->perPageEnabled = (bool) ($options['perPageEnabled'] ?? config('form-tool.list.perPageEnabled', true));

        $configuredPerPage = $options['perPageOptions'] ?? config('form-tool.list.perPageOptions', [20, 50, 100, 200]);
        $this->perPageOptions = array_values(array_unique(array_filter(array_map('intval', (array) $configuredPerPage), fn ($value) => $value > 0)));
        if (! $this->perPageOptions) {
            $this->perPageOptions = [20];
        }

        $defaultPerPage = (int) ($values['perPage'] ?? $defaults['perPage'] ?? config('form-tool.list.defaultPerPage', $this->perPageOptions[0]));
        $this->defaultPerPage = in_array($defaultPerPage, $this->perPageOptions, true)
            ? $defaultPerPage
            : $this->perPageOptions[0];

        $this->saveUrl = isset($options['saveUrl']) ? (string) $options['saveUrl'] : null;
        $this->canUpdate = (bool) ($options['canUpdate'] ?? false);
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function filters(): array
    {
        return $this->filters;
    }

    public function selectedColumns(): array
    {
        return $this->selectedColumns;
    }

    public function selectedFilters(): array
    {
        return $this->selectedFilters;
    }

    public function perPageOptions(): array
    {
        return $this->perPageOptions;
    }

    public function perPageEnabled(): bool
    {
        return $this->perPageEnabled;
    }

    public function defaultPerPage(): int
    {
        return $this->defaultPerPage;
    }

    public function perPage(Request $request): int
    {
        $requested = (int) $request->query('per_page');

        return in_array($requested, $this->perPageOptions, true) ? $requested : $this->defaultPerPage;
    }

    public function saveUrl(): ?string
    {
        return $this->saveUrl;
    }

    public function canUpdate(): bool
    {
        return $this->canUpdate && $this->saveUrl !== null;
    }

    public function validate(array $input): array
    {
        $columns = $this->allowedSelection($input['columns'] ?? [], $this->columns);
        $filters = $this->allowedSelection($input['filters'] ?? [], $this->filters);
        $perPage = (int) ($input['perPage'] ?? 0);

        $errors = [];
        if (! $columns) {
            $errors['columns'] = 'Select at least one column.';
        } elseif (count($columns) !== count(array_unique((array) ($input['columns'] ?? [])))) {
            $errors['columns'] = 'One or more selected columns are invalid.';
        }

        if (count($filters) !== count(array_unique((array) ($input['filters'] ?? [])))) {
            $errors['filters'] = 'One or more selected filters are invalid.';
        }

        if (! in_array($perPage, $this->perPageOptions, true)) {
            $errors['perPage'] = 'Select a valid default per-page limit.';
        }

        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        return [
            'columns' => $columns,
            'filters' => $filters,
            'perPage' => $perPage,
        ];
    }

    private function normalizeOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $key => $label) {
            if (is_int($key)) {
                $key = (string) $label;
                $label = ucfirst($key);
            }

            $key = trim((string) $key);
            if ($key !== '') {
                $normalized[$key] = (string) $label;
            }
        }

        return $normalized;
    }

    private function allowedSelection($selection, array $allowed): array
    {
        $selection = array_values(array_unique(array_map('strval', is_array($selection) ? $selection : [])));

        return array_values(array_intersect($selection, array_keys($allowed)));
    }
}
