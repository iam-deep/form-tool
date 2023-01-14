<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Encryption;
use Biswadeep\FormTool\Core\InputTypes\Common\IEncryptable;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Core\InputTypes\Common\ISearchable;

class TextareaType extends BaseInputType implements IEncryptable, ISearchable
{
    use Encryption;

    public int $type = InputType::TEXTAREA;
    public string $typeInString = 'textarea';

    public function getHTML()
    {
        $input = '<textarea class="'.implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.
            '" '.$this->raw.$this->inlineCSS.'>'.old($this->dbField, $this->value).'</textarea>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $value = $oldValue ?? $this->value;

        return '<textarea class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.
            $index.'" name="'.$key.'['.$index.']['.$this->dbField.']" '.$this->raw.$this->inlineCSS.'>'.$value.
            '</textarea>';
    }
}
