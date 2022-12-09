<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Encryption;
use Biswadeep\FormTool\Core\InputTypes\Common\IEncryptable;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Core\InputTypes\Common\ISearchable;

class HiddenType extends BaseInputType implements IEncryptable, ISearchable
{
    use Encryption;

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

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $value = $oldValue ?? $this->value;

        $input = '<input type="hidden" class="'.\implode(' ', $this->classes).'" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" value="'.$value.'" '.$this->raw.' />';

        return $input;
    }
}
