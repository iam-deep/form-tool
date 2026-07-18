<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\IVisibilityController;
use Deep\FormTool\Core\InputTypes\Common\Options;
use Deep\FormTool\Core\InputTypes\Common\VisibilityRules;
use Deep\FormTool\Exceptions\FormToolException;

class CheckboxType extends BaseInputType implements IVisibilityController
{
    use Options;
    use VisibilityRules;

    public int $type = InputType::CHECKBOX;
    public string $typeInString = 'checkbox';

    protected string $captionYes = 'Yes';
    protected string $captionNo = 'No';
    protected string $valueYes = '1';
    protected string $valueNo = '0';

    protected $singleOptions = [];

    public function __construct()
    {
        parent::__construct();

        $this->classes = [];
        $this->optionType = InputType::CHECKBOX;

        $this->isRemoveTrash = \config('isSoftDelete', true);
    }

    //region Setter
    public function required($isRequired = true): CheckboxType
    {
        $this->isRequired = $isRequired;

        if ($isRequired) {
            $this->validations['required'] = 'required';
        } else {
            if (isset($this->validations['required'])) {
                unset($this->validations['required']);
            }
        }

        return $this;
    }

    public function captions($captionYes, $captionNo = 'No')
    {
        $this->captionYes = $captionYes;
        $this->captionNo = $captionNo;

        return $this;
    }

    public function values($valueYes, $valueNo = 0)
    {
        if (! $valueYes) {
            throw new \InvalidArgumentException('Yes/On value cannot be: "'.$valueYes.'"');
        }

        $this->valueYes = $valueYes;
        $this->valueNo = $valueNo;

        return $this;
    }

    public function hide(string|array $fields, mixed $value, bool $isRequiredOnShow = false): static
    {
        $this->validateVisibilityTriggerValue($value);

        return $this->addVisibilityRule('hide', $fields, $value, $isRequiredOnShow);
    }

    public function show(string|array $fields, mixed $value, bool $isRequiredOnShow = false): static
    {
        $this->validateVisibilityTriggerValue($value);

        return $this->addVisibilityRule('show', $fields, $value, $isRequiredOnShow);
    }
    //endregion

    public function beforeValidation($data)
    {
        if ($this->visibilityRules && $data === null) {
            return $this->valueNo;
        }

        return null;
    }

    public function beforeStore($newData)
    {
        return $this->getFormValue($newData);
    }

    public function beforeUpdate($oldData, $newData)
    {
        return $this->getFormValue($newData);
    }

    private function getFormValue($newData)
    {
        $val = $newData->{$this->dbField} ?? null;
        if ($this->isMultiple) {
            if ($val === null) {
                return null;
            }

            // If we have multiple options then let's keep it in json
            // TODO: May be we have to make the option to keep this in other table
            return \json_encode($val);
        }

        // I don't know why I have put $val[0] as return value, but it's working with string value
        // but not working with integer value. So, I have to put this condition
        if (is_numeric($val)) {
            return $val;
        }

        // If we have no options or single option then let's use single values
        return $val[0] ?? $this->valueNo;
    }

    public function getHTML()
    {
        $this->createOptions();

        $visibilityAttributes = $this->getVisibilityAttributes();

        $value = old($this->dbField);
        if ($value === null) {
            $value = $this->value;
            if ($this->isMultiple) {
                $value = (array) \json_decode($this->value, true);
            } else {
                $value = (string) $value;
            }
        }

        $input = '';
        if (! $this->isMultiple) {
            foreach ($this->options as $val => $text) {
                $input .= '<label><input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.
                    $this->dbField.'" name="'.$this->dbField.'" value="'.$val.'"'.$visibilityAttributes.' '.
                    (\is_string($value) && $val == $value ? 'checked' : '').' '.$this->raw.$this->inlineCSS.' /> '.
                    $text.'</label> &nbsp; ';
                break;
            }
        } else {
            foreach ($this->options as $val => $text) {
                $input .= '<label><input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.
                    $this->dbField.'-'.preg_replace('/\s+/', '', $val).'" name="'.$this->dbField.'[]" value="'.$val.
                    '" '.(\is_array($value) && \in_array((string) $val, $value, true) ? 'checked' : '').' '.
                    $this->raw.$this->inlineCSS.' /> '.$text.'</label> &nbsp; &nbsp; ';
            }
        }

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $this->createOptions();

        $value = $oldValue ?? $this->value;

        $input = '';
        if (! $this->isMultiple) {
            foreach ($this->options as $val => $text) {
                $input .= '<label><input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.
                    $key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" value="'.$val.'" '.
                    (\is_string($value) && $val == $value ? 'checked' : '').' '.$this->raw.$this->inlineCSS.' /> '.
                    $text.'</label> &nbsp; ';
                break;
            }
        }

        return $input;
    }

    protected function getVisibilityAttributes(): string
    {
        if (! $this->visibilityRules) {
            return '';
        }

        if ($this->isMultiple) {
            throw new FormToolException('Visibility rules require a single-value controlling checkbox.');
        }

        $rules = htmlspecialchars(
            json_encode($this->visibilityRules, JSON_THROW_ON_ERROR),
            ENT_QUOTES,
            'UTF-8'
        );
        $uncheckedValue = htmlspecialchars(
            $this->normalizeVisibilityValue($this->valueNo),
            ENT_QUOTES,
            'UTF-8'
        );

        return ' data-form-tool-visibility="'.$rules.'"'
            .' data-form-tool-unchecked-value="'.$uncheckedValue.'"';
    }

    private function validateVisibilityTriggerValue(mixed $value): void
    {
        if (! is_scalar($value) && $value !== null) {
            throw new FormToolException('Checkbox visibility trigger value must be scalar or null.');
        }
    }

    private function normalizeVisibilityValue(mixed $value): string
    {
        return is_bool($value) ? ($value ? '1' : '0') : (string) $value;
    }
}
