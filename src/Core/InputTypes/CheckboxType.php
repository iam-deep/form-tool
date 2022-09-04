<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Options;

class CheckboxType extends BaseInputType
{
    use Options;

    public int $type = InputType::Checkbox;
    public string $typeInString = 'checkbox';

    public function __construct()
    {
        $this->classes = [];
        $this->isMultiple = true;
    }
    
    // Setter
    
    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        return $validations;
    }

    public function beforeValidation($data)
    {
        return null;
    }

    public function beforeUpdate($oldData, $newData)
    {
        $val = $newData->{$this->dbField};
        if ($this->isMultiple && $this->countOption > 1) {
            return \json_encode($val);
        }
        else {
            return $val;
        }
    }

    public function getHTML()
    {
        $value = old($this->dbField, $this->value);

        if ($this->isMultiple)
            $value = (array)\json_decode($this->value, true);

        $input = '';
        if ($this->options && \count($this->options)) {
            foreach ($this->options as $val => $text) {
                $input .= '<label>&nbsp; &nbsp;<input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'[]" value="'.$val.'" '.(\is_array($value) && \in_array($val, $value) ? 'checked' : '').' '.($this->isRequired ? 'required' : '').' '.$this->raw.' '.$this->inlineCSS.' /> '.$text.'</label> &nbsp; ';
            }

            if ($this->result) {
                foreach ($this->result as $row) {
                    $text = '';
                    if ($this->dbPatternFields) {
                        $values = [];
                        foreach ($this->dbPatternFields as $field) {
                            $field = \trim($field);
                            if (\property_exists($row, $field)) {
                                $values[] = $row->{$field};
                            } else {
                                $values[] = 'DB field "'.$field.'" not exists!';
                            }
                        }
    
                        $text = \vsprintf($this->dbTableTitle, $values);
                    } else {
                        $text = $row->{$this->dbTableTitle};
                    }
    
                    $input .= '<label>&nbsp; &nbsp;<input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'[]" value="'.$row->{$this->dbTableValue}.'" '.(\is_array($value) && \in_array($row->{$this->dbTableValue}, $value) ? 'checked' : '').' '.($this->isRequired ? 'required' : '').' '.$this->raw.' '.$this->inlineCSS.' /> '.$text.'</label> &nbsp; ';
                }
            }
        }
        else {
            $input = '<label>&nbsp; &nbsp;<input type="checkbox" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="1" '.(\is_string($value) && 1 == $value ? 'checked' : '').' '.($this->isRequired ? 'required' : '').' '.$this->raw.' '.$this->inlineCSS.' /></label>';
        }

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<input type="checkbox" class="'.\implode(' ', $this->classes).' input-sm" id="'.$this->dbField.'" name="'.$key.'['.$this->dbField.'][]" value="'.$value.'" '.($this->isRequired ? 'required' : '').' '.$this->raw.' '.$this->inlineCSS.' />';

        return $input;
    }
}
