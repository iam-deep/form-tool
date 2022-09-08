<?php

namespace Biswadeep\FormTool\Core\InputTypes;

class TextareaType extends BaseInputType
{
    public int $type = InputType::Textarea;
    public string $typeInString = 'textarea';

    public function getHTML()
    {
        $input = '<textarea class="'.implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" placeholder="'.$this->placeholder.'" '.$this->raw.$this->inlineCSS.' />'.old($this->dbField, $this->value).'</textarea>';

        return $this->htmlParentDiv($input);
    }
}
