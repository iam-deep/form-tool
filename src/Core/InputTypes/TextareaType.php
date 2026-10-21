<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\Encryption;
use Deep\FormTool\Core\InputTypes\Common\IEncryptable;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISearchable;
use Deep\FormTool\Core\InputTypes\Common\LengthValidation;

class TextareaType extends BaseInputType implements IEncryptable, ISearchable
{
    use Encryption;
    use LengthValidation;

    public int $type = InputType::TEXTAREA;
    public string $typeInString = 'textarea';

    public function getValidations($type)
    {
        return $this->lengthValidations(parent::getValidations($type), ['max:500']);
    }

    public function getTableValue()
    {
        return e(parent::getTableValue());
    }

    public function getHTML()
    {
        $input = '<textarea class="'.implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.
            '" '.$this->raw.$this->inlineCSS.'>'.e(old($this->dbField, $this->value)).'</textarea>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $value = $oldValue ?? $this->value;

        return '<textarea class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.
            $index.'" name="'.$key.'['.$index.']['.$this->dbField.']" '.$this->raw.$this->inlineCSS.'>'.e($value).
            '</textarea>';
    }
}
