<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\Encryption;
use Deep\FormTool\Core\InputTypes\Common\IEncryptable;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISearchable;

class HiddenType extends BaseInputType implements IEncryptable, ISearchable
{
    use Encryption;

    public int $type = InputType::HIDDEN;
    public string $typeInString = 'hidden';

    public function __construct()
    {
        parent::__construct();

        $this->classes = [];
    }

    public function getHTML()
    {
        return '<input type="hidden" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" '.
            'name="'.$this->dbField.'" value="'.old($this->dbField, $this->value).'" '.$this->raw.' />';
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $value = $oldValue ?? $this->value;

        return '<input type="hidden" class="'.\implode(' ', $this->classes).'" '.
            'id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" '.
            'value="'.$value.'" '.$this->raw.' />';
    }
}
