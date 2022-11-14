<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Core\Doc;
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
        $this->addScript();

        // We will only display password on validation errors
        $input = '<div class="input-group">
            <input type="password" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField).'" '.$this->raw.$this->inlineCSS.' />
            <span class="input-group-btn">
                <button class="btn btn-default toggle-password" data-id="'.$this->dbField.'" type="button" data-toggle="tooltip" title="Show Password"><i class="fa fa-eye"></i></button>
            </span>
        </div>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? '';

        $input = '<input type="password" class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" value="" '.$this->raw.$this->inlineCSS.' />';

        return $input;
    }

    private function addScript()
    {
        Doc::addJs('
        $(".toggle-password").on("click", function() {
            let field = $("#" + $(this).attr("data-id"));
            let type = field.attr("type") == "password" ? "text" : "password";
            field.attr("type", type);

            if (type == "password") {
                $(this).attr("title", "Show Password");
                $(this).find("i").removeClass("fa-eye-slash").addClass("fa-eye");
            } else {
                $(this).attr("title", "Hide Password");
                $(this).find("i").removeClass("fa-eye").addClass("fa-eye-slash");
            }
        });
        ', 'password-toggle');
    }
}
