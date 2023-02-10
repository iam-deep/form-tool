<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\CellDefinition;
use Deep\FormTool\Core\InputTypes\Common\ICustomType;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\TableField;
use Illuminate\Support\Facades\Session;

class BaseInputType
{
    protected $bluePrint = null;

    protected int $type = InputType::TEXT;

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

    // For mulitle table alias
    protected ?string $tableName = null;
    protected ?string $alias = null;

    // CSS
    protected $classes = [];
    protected $inlineCSS = '';

    public function __construct()
    {
        $this->classes = [\config('form-tool.styleClass.input-field')];
    }

    public function init($bluePrint, string $dbField, string $label = null)
    {
        $this->bluePrint = $bluePrint;
        $this->dbField = \trim($dbField);
        $this->label = $label ?: \ucfirst($this->dbField);
    }

    //region Setter
    public function setDbField($dbField): BaseInputType
    {
        $this->dbField = $dbField;

        return $this;
    }

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

    public function table($tableName, $alias = null)
    {
        $this->tableName = trim($tableName);
        $this->alias = trim($alias) ?: $this->tableName;
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

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getAlias()
    {
        return $this->alias ?: $this->bluePrint->getForm()->getModel()->getAlias();
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

    // getValue is for before creating form not before form save
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
        return $this->getNiceValue($this->value);
    }

    public function getNiceValue($value)
    {
        return $value;
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        switch ($action) {
            case 'update':
                $oldValue = $this->getNiceValue($oldValue);
                $newValue = $this->getNiceValue($this->value);
                if ($oldValue != $newValue) {
                    return [
                        'type' => 'text',
                        'data' => [$oldValue ?? '', $newValue ?? ''],
                    ];
                }

                break;

            case 'create':
            case 'delete':
            case 'destroy':
            case 'duplicate':
            case 'restore':
                return $this->getNiceValue($this->value) ?? '';

                break;

            default:
        }

        return '';
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

    final public function getTableCell(TableField $tableField)
    {
        return CellDefinition::Input($tableField, $this);
    }

    protected function htmlParentDiv($input): string
    {
        $errors = Session::get('errors');
        $error = $errors ? $errors->first($this->dbField) : null;

        $field = new \stdClass();
        $field->type = $this->typeInString;
        $field->error = $error;
        $field->help = $this->help;
        $field->isRequired = $this->isRequired;
        $field->dbField = $this->dbField;
        $field->input = $input;
        $field->label = $this->label;
        $field->controller = $this->bluePrint->getForm()->getResource();

        $data['field'] = $field;

        return \view('form-tool::form.base_input', $data)->render();
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
