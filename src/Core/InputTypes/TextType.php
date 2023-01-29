<?php

namespace Deep\FormTool\Core\InputTypes;

use Closure;
use Deep\FormTool\Core\InputTypes\Common\Encryption;
use Deep\FormTool\Core\InputTypes\Common\IEncryptable;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISearchable;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TextType extends BaseInputType implements IEncryptable, ISearchable
{
    use Encryption {
        getValue as protected getDecryptedValue;
    }

    public int $type = InputType::TEXT;
    public string $typeInString = 'text';

    public string $inputType = 'text';

    public bool $isUnique = false;
    public $uniqueClosure = null;

    private bool $isSlug = false;
    private bool $forceNullIfEmpty = false;

    public function unique(Closure $uniqueCondition = null)
    {
        $this->isUnique = true;
        $this->uniqueClosure = $uniqueCondition;

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
        $this->getDecryptedValue();

        return $this->value;
    }

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        if ($this->isUnique) {
            $model = $this->bluePrint->getForm()->getModel();

            if ($type == 'store') {
                $rule = Rule::unique($model->getTableName(), $this->dbField);
                if ($this->uniqueClosure) {
                    $uniqueClosure = $this->uniqueClosure;
                    $rule->where($uniqueClosure);
                }

                $validations[] = $rule;
            } else {
                $rule = Rule::unique($model->getTableName(), $this->dbField)
                    ->ignore(
                        $this->bluePrint->getForm()->getId(),
                        $model->isToken() ? $model->getTokenCol() : $model->getPrimaryId()
                    );

                if ($this->uniqueClosure) {
                    $uniqueClosure = $this->uniqueClosure;
                    $rule->where($uniqueClosure);
                }

                $validations[] = $rule;
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

    public function beforeStore(object $newData)
    {
        if ($this->value === null && $this->forceNullIfEmpty) {
            return null;
        }

        // If the field is Number type and is optional and there is no value then put default value as 0
        // As the default value of a number should be 0
        if ($this->type == InputType::NUMBER && ! $this->isRequired && ! $this->value) {
            return 0;
        }

        return $this->value;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        return $this->beforeStore($newData);
    }

    public function getHTML()
    {
        $input = '<input type="'.$this->inputType.'" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.
            '" name="'.$this->dbField.'" value="'.old($this->dbField, $this->value).'" '.
            $this->raw.$this->inlineCSS.' />';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $value = $oldValue ?? $this->value;

        return '<input type="'.$this->inputType.'" class="'.\implode(' ', $this->classes).' input-sm" id="'
            .$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" value="'.$value.
            '" '.$this->raw.$this->inlineCSS.' />';
    }
}
