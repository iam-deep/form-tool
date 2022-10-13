<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\CellDefinition;
use Biswadeep\FormTool\Core\InputTypes\Common\ICustomType;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Support\Facades\Session;

class BaseInputType
{
    protected $bluePrint = null;

    protected int $type = InputType::Text;

    protected string $dbField = '';
    protected string $label = '';
    protected $value = '';
    protected $defaultValue = null;

    protected string $placeholder = '';
    protected string $help = '';
    protected $raw = '';

    // For multiple array field type
    protected bool $isArray = false;
    protected string $parentField = '';
    protected int $index = -1;

    // Validations
    protected $validations = [];
    protected $validationMessages = [];
    protected bool $isRequired = false;
    protected bool $isError = false;
    protected string $error = '';

    // CSS
    protected $classes = ['form-control'];
    protected $inlineCSS = '';

    // Design Class Constants
    protected const classDiv = 'form-group';
    protected const classDivError = 'has-error';
    protected const classLabel = '';
    protected const classDisplayError = 'help-block';

    public function init($bluePrint, string $dbField, string $label = null)
    {
        $this->bluePrint = $bluePrint;
        $this->dbField = \trim($dbField);
        $this->label = $label ?: \ucfirst($this->dbField);
    }

    //region Setter
    public function label(string $label): BaseInputType
    {
        $this->label = $label;

        return $this;
    }

    public function default($value): BaseInputType
    {
        $this->value = $this->defaultValue = $value;

        return $this;
    }

    public function placeholder(string $text): BaseInputType
    {
        $this->raw('placeholder="'.$text.'"');

        return $this;
    }

    public function help(string $helpText): BaseInputType
    {
        $this->help = $helpText;

        return $this;
    }

    public function raw(string $rawAttributes): BaseInputType
    {
        $this->raw .= $rawAttributes.' ';

        return $this;
    }

    public function removeRaw(string $rawAttributes): BaseInputType
    {
        $this->raw = \str_replace($rawAttributes.' ', '', $this->raw);

        return $this;
    }

    public function required(bool $isRequired = true): BaseInputType
    {
        $this->isRequired = $isRequired;

        if ($isRequired) {
            $this->validations['required'] = 'required';
            $this->raw('required');
        } else {
            if (isset($this->validations['required'])) {
                unset($this->validations['required']);
            }

            $this->removeRaw('required');
        }

        return $this;
    }

    public function validations($rules, $messages = [], bool $replace = false)
    {
        if ($rules) {
            if (\is_string($rules)) {
                $rules = \explode('|', $rules);
            }

            $this->validations = array_merge($this->validations, $rules);

            if ($replace) {
                $this->validations = $rules;
                if (in_array('required', $rules)) {
                    $this->required();
                } else {
                    $this->required(false);
                }
            }
        }

        if ($messages) {
            $this->validationMessages = array_merge($this->validationMessages, $messages);

            if ($replace) {
                $this->validationMessages = $messages;
            }
        }

        return $this;
    }

    public function readonly(): BaseInputType
    {
        $this->raw('readonly');

        return $this;
    }

    public function disabled(): BaseInputType
    {
        $this->raw('disabled');

        return $this;
    }

    public function addClass($classes): BaseInputType
    {
        if (\is_array($classes)) {
            $this->classes = array_merge($this->classes, $classes);
        } else {
            $this->classes[] = \trim($classes);
        }

        return $this;
    }

    public function removeClass($classes): BaseInputType
    {
        if (\is_array($classes)) {
            foreach ($classes as $c) {
                if (($key = array_search($c, $this->classes)) !== false) {
                    unset($this->classes[$key]);
                }
            }
        } else {
            if (($key = array_search($classes, $this->classes)) !== false) {
                unset($this->classes[$key]);
            }
        }

        return $this;
    }

    public function addStyle($style): BaseInputType
    {
        $this->inlineCSS .= $style.'';
    }
    //endregion

    //region Callbacks

    public function beforeValidation($data)
    {
        return null;
    }

    public function beforeStore(object $newData)
    {
        return null;
    }

    public function afterStore(object $newData)
    {
        return null;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        return null;
    }

    public function afterUpdate(object $oldData, object $newData)
    {
        return null;
    }

    public function beforeDestroy(object $oldData)
    {
        return null;
    }

    public function afterDestroy(object $oldData)
    {
        return null;
    }

    //endregion

    /* Internal functions */
    public function getValidations($type)
    {
        // TODO: Check required when validation passed as string

        if (! \in_array('required', $this->validations)) {
            $this->validations['required'] = 'nullable';
        }

        return $this->validations;
    }

    public function getValidationMessages()
    {
        $messages = [];
        foreach ($this->validationMessages as $rule => $message) {
            $messages[$this->dbField.'.'.$rule] = $message;
        }

        return $messages;
    }

    final public function getType()
    {
        return $this->type;
    }

    final public function getDbField(): string
    {
        return $this->dbField;
    }

    final public function isRequired(): bool
    {
        return $this->isRequired;
    }

    final public function getLabel()
    {
        return $this->label;
    }

    final public function setIndex($parentField, $index)
    {
        $this->isArray = true;
        $this->parentField = $parentField;
        $this->index = $index;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getRawValue()
    {
        return $this->value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getTableValue()
    {
        return $this->value;
    }

    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    public function getHelp()
    {
        return $this->help;
    }

    public function getRaw()
    {
        return $this->raw;
    }

    public function getClasses()
    {
        return $this->classes;
    }

    public function getInlineCss()
    {
        return $this->inlineCSS;
    }

    final public function getTableCell()
    {
        return CellDefinition::Input($this);
    }

    protected function htmlParentDiv($input): string
    {
        $errors = Session::get('errors');
        $error = $errors ? $errors->first($this->dbField, '<p class="help-block">:message</p>') : null;

        $help = $this->help ? ' <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="right" title="'.$this->help.'"></i>' : '';

        $required = ($this->isRequired ? ' <span class="text-danger">*</span>' : '');

        return '<div class="'.self::classDiv.' '.($error ? self::classDivError : null).'">
            <label for="'.$this->dbField.'">'.$this->label.$help.$required.'</label>
            '.$input.$error.'
        </div>';
    }

    public function toObj($type)
    {
        $field = new \stdClass();
        $field->type = $this->type;
        $field->dbField = $this->dbField;
        $field->label = $this->label;
        $field->defaultValue = $this->defaultValue;
        $field->placeholder = $this->placeholder;
        $field->help = $this->help;
        $field->raw = $this->raw;
        $field->validations = $this->getValidations($type);
        $field->validationMessages = $this->getValidationMessages();
        $field->isRequired = $this->isRequired;
        $field->classes = $this->classes;
        $field->inlineCSS = $this->inlineCSS;

        return $field;
    }
}
