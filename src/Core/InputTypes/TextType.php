<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TextType extends BaseInputType
{
    public int $type = InputType::Text;
    public string $typeInString = 'text';

    public string $inputType = 'text';

    public bool $isEncrypted = false;
    public bool $isUnique = false;
    private bool $isSlug = false;

    public function encrypt(): TextType
    {
        $this->isEncrypted = true;

        return $this;
    }

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
                    ($model->isToken() ? $model->getToken() : $model->getPrimaryId())
                );
            }
        }

        return $validations;
    }

    public function getValue()
    {
        return $this->value;

        /*Crypt::encryptString($request->token);

        try {
            $decrypted = Crypt::decryptString($encryptedValue);
        } catch (DecryptException $e) {
            //
        }*/
    }

    public function getTableValue()
    {
        return $this->value;
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
