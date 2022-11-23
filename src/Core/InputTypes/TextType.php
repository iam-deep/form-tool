<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Encryption;
use Biswadeep\FormTool\Core\InputTypes\Common\IEncryptable;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Support\Str;

class TextType extends BaseInputType implements IEncryptable
{
    use Encryption {
        getValue as protected getDecryptedValue;
    }

    public int $type = InputType::Text;
    public string $typeInString = 'text';

    public string $inputType = 'text';

    public bool $isUnique = false;
    private bool $isSlug = false;
    private bool $forceNullIfEmpty = false;

    public function unique()
    {
        $this->isUnique = true;

        return $this;
    }

    public function slug()
    {
        $this->isSlug = true;
        $this->isUnique = true;

        return $this;
    }

    public function forceNullIfEmpty(bool $flag = true)
    {
        $this->forceNullIfEmpty = $flag;

        return $this;
    }

    public function getValue()
    {
        if (! $this->value && $this->forceNullIfEmpty) {
            return null;
        }

        // If the field is Number type and is optional and there is no value then put default value as 0
        // As the default value of a number should be 0
        if ($this->type == InputType::Number && ! $this->isRequired && ! $this->value) {
            return 0;
        }

        $this->getDecryptedValue();

        return $this->value;
    }

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        if ($this->isUnique) {
            $model = $this->bluePrint->form->getModel();

            if ($type == 'store') {
                $validations[] = \sprintf(
                    'unique:%s,%s',
                    $model->getTableName(),
                    $this->dbField
                );
            } else {
                $validations[] = \sprintf(
                    'unique:%s,%s,%s,%s',
                    $model->getTableName(),
                    $this->dbField,
                    $this->bluePrint->form->getId(),
                    ($model->isToken() ? $model->getTokenCol() : $model->getPrimaryId())
                );
            }
        }

        return $validations;
    }

    public function beforeValidation($data)
    {
        if ($this->isSlug) {
            return Str::slug($data);
        }

        return null;
    }

    public function getHTML()
    {
        $input = '<input type="'.$this->inputType.'" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField, $this->value).'" '.$this->raw.$this->inlineCSS.' />';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<input type="'.$this->inputType.'" class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" value="'.$value.'" '.$this->raw.$this->inlineCSS.' />';

        return $input;
    }
}
