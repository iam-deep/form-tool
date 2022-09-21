<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Options;

class CheckboxType extends BaseInputType
{
    use Options;

    public int $type = InputType::Checkbox;
    public string $typeInString = 'checkbox';

    protected string $captionYes = 'Yes';
    protected string $captionNo = 'No';
    protected string $valueYes = '1';
    protected string $valueNo = '0';

    protected $singleOptions = [];

    public function __construct()
    {
        $this->classes = [];
        $this->optionType = InputType::Checkbox;
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
            throw new \Exception('Yes/On value cannot be: "'.$valueYes.'"');
        }

        $this->valueYes = $valueYes;
        $this->valueNo = $valueNo;

        return $this;
    }
    //endregion

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
        $val = $newData->{$this->dbField};
        if ($this->isMultiple) {
            if ($val === null) {
                return null;
            }

            // If we have multiple options then let's keep it in json
            return \json_encode($val);
        }

        // If we have no options or single option then let's use single values
        return $val[0] ?? $this->valueNo;
    }

    public function getHTML()
    {
        $this->createOptions();

        $value = old($this->dbField);
        if ($value === null) {
            $value = $this->value;
            if ($this->isMultiple) {
                $value = (array) \json_decode($this->value, true);
            } else {
                $value = [(string) $value];
            }
        }

        $input = '';
        foreach ($this->options as $val => $text) {
            $input .= '<label>&nbsp; &nbsp;<input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'[]" value="'.$val.'" '.(\is_array($value) && \in_array((string) $val, $value, true) ? 'checked' : '').' '.$this->raw.$this->inlineCSS.' /> '.$text.'</label> &nbsp; ';
        }

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        // TODO: Everything here

        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<input type="checkbox" class="'.\implode(' ', $this->classes).' input-sm" id="'.$this->dbField.'" name="'.$key.'['.$this->dbField.'][]" value="'.$value.'" '.$this->raw.$this->inlineCSS.' />';

        return $input;
    }
}