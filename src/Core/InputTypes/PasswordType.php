<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Illuminate\Support\Facades\Hash;

class PasswordType extends BaseInputType
{
    public int $type = InputType::Password;
    public string $typeInString = 'password';

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        return $validations;
    }

    public function getTableValue()
    {
        if ($this->value) {
            return '*****';
        }

        return '';
    }

    public function beforeStore(object $newData)
    {
        if ($newData->{$this->dbField} == '') {
            return null;
        }

        return Hash::make($newData->{$this->dbField});
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        // If user doesn't provide any password then let's put the previous password
        if ($newData->{$this->dbField} == '') {
            return $oldData->{$this->dbField};
        }

        return Hash::make($newData->{$this->dbField});
    }

    public function getHTML()
    {
        $input = '<input type="password" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField).'" placeholder="'.$this->placeholder.'" '.$this->raw.$this->inlineCSS.' />';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? '';

        $input = '<input type="password" class="'.\implode(' ', $this->classes).' input-sm" id="'.$this->dbField.'" name="'.$key.'['.$this->dbField.'][]" value="" placeholder="'.$this->placeholder.'" '.$this->raw.$this->inlineCSS.' />';

        return $input;
    }
}
