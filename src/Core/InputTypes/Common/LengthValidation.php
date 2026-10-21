<?php

namespace Deep\FormTool\Core\InputTypes\Common;

trait LengthValidation
{
    public function min(int $length): static
    {
        if ($length < 0) {
            throw new \InvalidArgumentException('Minimum length must be zero or greater.');
        }

        $this->validations(['min:'.$length]);

        return $this;
    }

    public function max(int $length): static
    {
        if ($length < 0) {
            throw new \InvalidArgumentException('Maximum length must be zero or greater.');
        }

        $this->validations(['max:'.$length]);

        return $this;
    }

    protected function lengthValidations(array $rules, array $defaultMaxRules): array
    {
        $limits = [];
        foreach ($rules as $key => $rule) {
            if (is_string($rule) && preg_match('/^(min|max):/i', $rule, $matches)) {
                $limits[strtolower($matches[1])] = $rule;
                unset($rules[$key]);
            }
        }

        if (! isset($limits['max'])) {
            $rules = array_merge($rules, $defaultMaxRules);
        }

        return array_merge($rules, array_values($limits));
    }
}
