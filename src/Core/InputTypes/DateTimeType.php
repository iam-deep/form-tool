<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\Doc;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Support\DTConverter;

class DateTimeType extends BaseFilterType
{
    public int $type = InputType::DateTime;
    public string $typeInString = 'datetime';

    protected $dbFormat = '';
    protected $niceFormat = '';

    protected $pickerFormatDateTime = 'DD-MM-YYYY hh:mm A';
    protected $pickerFormatDate = 'DD-MM-YYYY';
    protected $pickerFormatTime = 'hh:mm A';

    protected $isConvertToLocal = true;

    public function __construct()
    {
        $this->dbFormat = DTConverter::$dbFormatDateTime;
        $this->niceFormat = DTConverter::$niceFormatDateTime;

        $this->classes[] = 'datetime-picker';
        $this->placeholder('Click to select date and time');
        $this->setFilterOptions(['range']);

        // This style is specific to this date picker plugin for the multiple table dates to work properly
        $this->inlineCSS = 'style="position:relative"';
    }

    public function convert(bool $isConvertToLocal = true)
    {
        $this->isConvertToLocal = $isConvertToLocal;

        return $this;
    }

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        $validations[] = 'date_format:'.$this->niceFormat;

        return $validations;
    }

    public function getTableValue()
    {
        return $this->modifyFormat($this->value);
    }

    public function beforeStore(object $newData)
    {
        $val = \trim($newData->{$this->dbField});

        return DTConverter::toDb($val, $this->dbFormat, $this->isConvertToLocal);
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $val = \trim($newData->{$this->dbField});

        return DTConverter::toDb($val, $this->dbFormat, $this->isConvertToLocal);
    }

    public function getHTML()
    {
        $this->setDependencies();

        $input = '<div class="input-group">
            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            <input type="text" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField, $this->modifyFormat($this->value)).'" '.$this->raw.$this->inlineCSS.' autocomplete="off" />
        </div>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $this->setDependencies();

        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->modifyFormat($this->value);

        $input = '<div class="input-group">
            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            <input type="text" class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" value="'.$value.'" '.$this->raw.$this->inlineCSS.' autocomplete="off" />
        </div>';

        return $input;
    }

    private function modifyFormat($value)
    {
        return DTConverter::toNice($value, $this->niceFormat, $this->isConvertToLocal);
    }

    private function setDependencies()
    {
        Doc::addCssLink('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css');

        Doc::addJsLink('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js');
        Doc::addJsLink('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js');

        $this->pickerFormatDateTime = \trim(config('form-tool.pickerFormatDateTime', $this->pickerFormatDateTime));
        $this->pickerFormatDate = \trim(config('form-tool.pickerFormatDate', $this->pickerFormatDate));
        $this->pickerFormatTime = \trim(config('form-tool.pickerFormatTime', $this->pickerFormatTime));

        Doc::addJs('
        // Date and DateTimePicker
        $(".datetime-picker").datetimepicker({format: "'.$this->pickerFormatDateTime.'", useCurrent: false});
        $(".date-picker").datetimepicker({format: "'.$this->pickerFormatDate.'", useCurrent: false});
        $(".time-picker").datetimepicker({format: "'.$this->pickerFormatTime.'", useCurrent: false});
        ',
            'datetime'
        );
    }

    public function applyFilter($query, $operator = '=')
    {
        if ($this->value !== null) {
            $this->value = DTConverter::toDb($this->value, $this->dbFormat, $this->isConvertToLocal);
            $query->where($this->dbField, $operator, $this->value);
        }
    }

    public function getFilterHTML()
    {
        $this->setDependencies();

        $input = '<div class="input-group">
            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            <input type="text" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" value="'.old($this->dbField, $this->value).'" '.$this->raw.$this->inlineCSS.' autocomplete="off" />
        </div>';

        return $this->htmlParentDivFilter($input);
    }
}
