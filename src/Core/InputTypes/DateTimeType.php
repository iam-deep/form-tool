<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\Crud;

class DateTimeType extends BaseInputType
{
    public int $type = InputType::DateTime;
    public string $typeInString = 'datetime';

    protected $dbFormat = 'Y-m-d H:i:s';
    protected $niceFormat = 'd-m-Y h:i A';

    protected $pickerFormatDateTime = 'DD-MM-YYYY hh:mm A';
    protected $pickerFormatDate = 'DD-MM-YYYY';
    protected $pickerFormatTime = 'hh:mm A';

    public function __construct()
    {
        $this->niceFormat = \trim(config('form-tool.formatDateTime', $this->niceFormat));

        $this->classes[] = 'datetime-picker';

        // This style is specific to this date picker plugin for the multiple table dates to work properly
        $this->inlineCSS = 'style="position:relative"';
    }

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        $validations[] = 'date_format:'.$this->niceFormat;

        return $validations;
    }

    public function getTableValue()
    {
        if ($this->value) {
            return \date($this->niceFormat, \strtotime($this->value));
        }

        return null;
    }

    public function beforeStore(object $newData)
    {
        $val = \trim($newData->{$this->dbField});
        if (! $val) {
            return null;
        }

        return \date($this->dbFormat, \strtotime($val));
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $val = \trim($newData->{$this->dbField});
        if (! $val) {
            return null;
        }

        return \date($this->dbFormat, \strtotime($val));
    }

    public function getHTML()
    {
        $this->setDependencies();

        $input = '<input type="text" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField, $this->modifyFormat($this->value)).'" '.($this->isRequired ? 'required' : '').' placeholder="'.$this->placeholder.'" '.$this->raw.' '.$this->inlineCSS.' autocomplete="off" />';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $this->setDependencies();

        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->modifyFormat($this->value);

        $input = '<input type="text" class="'.\implode(' ', $this->classes).' input-sm" name="'.$key.'['.$this->dbField.'][]" value="'.$value.'" '.($this->isRequired ? 'required' : '').' placeholder="'.$this->placeholder.'" '.$this->raw.' '.$this->inlineCSS.' autocomplete="off" />';

        return $input;
    }

    private function modifyFormat($value)
    {
        if (! $value) {
            return '';
        }

        return \date($this->niceFormat, \strtotime($value));
    }

    private function setDependencies()
    {
        Crud::addCssLink('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css');

        Crud::addJsLink('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js');
        Crud::addJsLink('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js');

        $this->pickerFormatDateTime = \trim(config('form-tool.pickerFormatDateTime', $this->pickerFormatDateTime));
        $this->pickerFormatDate = \trim(config('form-tool.pickerFormatDate', $this->pickerFormatDate));
        $this->pickerFormatTime = \trim(config('form-tool.pickerFormatTime', $this->pickerFormatTime));

        Crud::addJs(
            '
        // Date and DateTimePicker
        $(".datetime-picker").datetimepicker({format: "'.$this->pickerFormatDateTime.'", useCurrent: false});
        $(".date-picker").datetimepicker({format: "'.$this->pickerFormatDate.'", useCurrent: false});
        $(".time-picker").datetimepicker({format: "'.$this->pickerFormatTime.'", useCurrent: false});
        ',
            'datetime'
        );
    }
}
