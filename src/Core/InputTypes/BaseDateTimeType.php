<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Support\DTConverter;

class BaseDateTimeType extends BaseFilterType
{
    public int $type = InputType::DATE_TIME;
    public string $typeInString = 'datetime';

    protected $dbFormat = '';
    protected $niceFormat = '';

    protected $pickerFormatDateTime = 'DD-MM-YYYY hh:mm A';
    protected $pickerFormatDate = 'DD-MM-YYYY';
    protected $pickerFormatTime = 'hh:mm A';

    protected $isConvertToLocal = true;

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

    public function getNiceValue($value)
    {
        return $this->modifyFormat($value);
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        $newValue = $this->value;

        if ($action == 'update') {
            if ($oldValue != $newValue) {
                return [
                    'type' => $this->typeInString,
                    'data' => [$oldValue ?: '', $newValue ?: ''],
                ];
            }

            return '';
        }

        return $newValue ? ['type' => $this->typeInString, 'data' => $newValue] : '';
    }

    public function beforeStore(object $newData)
    {
        $val = \trim($newData->{$this->dbField});

        $this->value = DTConverter::toDb($val, $this->dbFormat, $this->isConvertToLocal);

        return $this->value;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        return $this->beforeStore($newData);
    }

    public function getHTML()
    {
        $this->setDependencies();

        $data['input'] = (object) [
            'type' => 'single',
            'column' => $this->dbField,
            'rawValue' => $this->value,
            'value' => $this->modifyFormat($this->value),
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
        ];

        return $this->htmlParentDiv(\view('form-tool::form.input_types.datetime', $data)->render());
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $this->setDependencies();

        $value = $oldValue ?? $this->modifyFormat($this->value);

        $id = $key.'-'.$this->dbField.'-'.$index;
        $name = $key.'['.$index.']['.$this->dbField.']';

        $data['input'] = (object) [
            'type' => 'multiple',
            'key' => $key,
            'index' => $index,
            'column' => $this->dbField,
            'rawValue' => $this->value,
            'value' => $value,
            'oldValue' => $oldValue,
            'id' => $id,
            'name' => $name,
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
        ];

        return \view('form-tool::form.input_types.datetime', $data)->render();
    }

    private function modifyFormat($value)
    {
        return DTConverter::toNice($value, $this->niceFormat, $this->isConvertToLocal);
    }

    private function setDependencies()
    {
        Doc::addCssLink('https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/css/bootstrap-datetimepicker.min.css');

        Doc::addJsLink('https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js');
        Doc::addJsLink(
            'https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datetimepicker/4.17.47/js/bootstrap-datetimepicker.min.js'
        );

        $this->pickerFormatDateTime = \trim(config('form-tool.pickerFormatDateTime', $this->pickerFormatDateTime));
        $this->pickerFormatDate = \trim(config('form-tool.pickerFormatDate', $this->pickerFormatDate));
        $this->pickerFormatTime = \trim(config('form-tool.pickerFormatTime', $this->pickerFormatTime));

        Doc::addJs(
            '
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

            $query->where($this->getAlias().'.'.$this->dbField, $operator, $this->value);
        }
    }

    public function getFilterHTML()
    {
        $this->setDependencies();

        $input = '<div class="input-group">
            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
            <input type="text" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.
                $this->dbField.'" value="'.old($this->dbField, $this->value).'" '.$this->raw.$this->inlineCSS.
                ' autocomplete="off" />
        </div>';

        return $this->htmlParentDivFilter($input);
    }
}
