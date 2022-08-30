<?php

namespace Biswadeep\FormTool\Core\InputTypes;

class TextareaType extends BaseInputType
{
    public int $type = InputType::Textarea;
    public string $typeInString = 'textarea';

    public function getHTML()
    {
        $input = '<textarea class="'.implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" '.($this->isRequired ? 'required' : '').' '.$this->raw.' '.$this->inlineCSS.' />'.old($this->dbField, $this->value).'</textarea>';

        return $this->htmlParentDiv($input);
    }
}
