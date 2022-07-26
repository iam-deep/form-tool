<?php

namespace Biswadeep\FormTool\Http\Libraries\InputTypes;

use Biswadeep\FormTool\Http\Libraries\CellDefinition;

interface ICustomType
{
    public function getHTML();
}

abstract class InputType
{
    // TextFields
    const Text = 0;
    const Number = 1;
    const Email = 2;
    const Password = 3;
    const Hidden = 4;

    // Radio & Checkbox
    const Radio = 10;
    const Checkbox = 11;

    // Dropdown
    const Select = 20;

    // File Browser
    const Image = 30;
    const File = 31;
    
    // DateTime
    const Date = 40;
    const Time = 41;
    const DateTime = 42;

    // Textare & Editor
    const Textarea = 50;
    const CkEditor = 51;

    // Custom
    const Custom = 99;
}

class BaseInputType
{
    protected $dataModel = null;

    protected int $type = InputType::Text;

    protected string $dbField = '';
    protected string $label = '';
    protected $value = '';
    protected $defaultValue = null;

    protected string $placeholder = '';
    protected string $help = '';
    protected $raw = '';

    // Validations
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

    public function init($dataModel, string $dbField, string $label = null)
    {
        $this->dataModel = $dataModel;
        $this->dbField = trim($dbField);
        $this->label = $label ?: \ucfirst($this->dbField);
    }

    public function label($label) : BaseInputType
    {
        $this->label = $label;

        return $this;
    }

    public function default($value) : BaseInputType
    {
        $this->value = $this->defaultValue = $value;

        return $this;
    }

    public function placeholder($text) : BaseInputType
    {
        $this->placeholder = $text;

        return $this;
    }

    public function help($helpText) : BaseInputType
    {
        $this->help = $helpText;

        return $this;
    }

    public function raw($rawAttributes) : BaseInputType
    {
        $this->raw .= $rawAttributes . ' ';

        return $this;
    }

    public function required() : BaseInputType
    {
        $this->isRequired = true;

        return $this;
    }

    public function readonly() : BaseInputType
    {
        $this->raw .= 'readonly ';

        return $this;
    }

    public function disabled() : BaseInputType
    {
        $this->raw .= 'disabled ';

        return $this;
    }

    public function addClass($classes) : BaseInputType
    {
        if (is_array($classes))
            $this->classes = array_merge($this->classes, $classes);
        else
            $this->classes[] = trim($classes);

        return $this;
    }

    public function removeClass($classes) : BaseInputType
    {
        if (is_array($classes)) {
            foreach ($classes as $c) {
                unset($this->classes[$c]);
            }
        }
        else {
            unset($this->classes[trim($classes)]);
        }

        return $this; 
    }

    public function addStyle($style) : BaseInputType
    {
        $this->inlineCSS .= $style . '';
    }

    #region Callbacks

    public function beforeStore(object $newData)
    {

    }

    public function afterStore(object $newData)
    {

    }

    public function beforeUpdate(object $oldData, object $newData)
    {

    }

    public function afterUpdate(object $oldData, object $newData)
    {

    }

    public function beforeDestroy(object $oldData)
    {

    }

    public function afterDestroy(object $oldData)
    {
        
    }

    #endregion

    /* Internal functions */
    public function getValidations($type)
    {
        $validations = [];
        if ($this->isRequired)
            $validations[] = 'required';
        else
            $validations[] = 'nullable';

        return $validations;
    }

    public final function getType()
    {
        return $this->type;
    }

    public final function getDbField() : string
    {
        return $this->dbField;
    }

    public final function isRequired() : bool
    {
        return $this->isRequired;
    }

    public final function getLabel()
    {
        return $this->label;
    }

    public function setValue($value)
    {
        $this->value = $value;
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

    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    public function getTableValue()
    {
        return $this->value;
    }

    public final function getTableCell()
    {
        return CellDefinition::Input($this);
    }

    protected function htmlParentDiv($input) : string
    {
        return '<div class="'. self::classDiv .' @if ($errors->has("'. $this->dbField .'")) '.self::classDivError.' @endif">
            <label for="'. $this->dbField .'">'. $this->label .'
            '. ($this->help ? ' <i class="fa fa-question-circle" data-toggle="tooltip" data-placement="right" title="'. $this->help .'"></i>' : '') .'
            </label>
            '. $input .'
            {!! $errors->first("'. $this->dbField .'", \'<p class="help-block">:message</p>\') !!}
        </div>';
    }
}