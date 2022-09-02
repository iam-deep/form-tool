<?php

namespace Biswadeep\FormTool\Core\InputTypes;

class HiddenType extends BaseInputType
{
    public int $type = InputType::Hidden;
    public string $typeInString = 'hidden';

    public function __construct()
    {
        $this->classes = [];
    }

    public function getHTML()
    {
        $input = '<input type="hidden" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField, $this->value).'" '.$this->raw.' />';

        return $input;
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<input type="hidden" class="'.\implode(' ', $this->classes).'" name="'.$key.'['.$this->dbField.'][]" value="'.$value.'" '.$this->raw.' />';

        return $input;
    }
}
