<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Encryption;
use Biswadeep\FormTool\Core\InputTypes\Common\IEncryptable;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;

class TextareaType extends BaseInputType implements IEncryptable
{
    use Encryption;

    public int $type = InputType::Textarea;
    public string $typeInString = 'textarea';

    public function getHTML()
    {
        $input = '<textarea class="'.implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" '.$this->raw.$this->inlineCSS.'>'.old($this->dbField, $this->value).'</textarea>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<textarea class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" '.$this->raw.$this->inlineCSS.'>'.$value.'</textarea>';

        return $input;
    }
}
