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

    public function getImportValue($value)
    {
        // Currently we are using excel dates
        return $this->getDateFromExcel($value);

        // try {
        //     $value = date($this->niceFormat, strtotime($value));
        //     if ($value) {
        //         return DTConverter::toDb($value, $this->dbFormat, $this->isConvertToLocal);
        //     }
        // } catch(\Exception $e) {
        //     return null;
        // }

        // return null;
    }

    public function getExportValue($value)
    {
        if (! $value) {
            return null;
        }

        return date('d-M-Y h:i a', strtotime($value));
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
        $val = \trim($newData->{$this->dbField} ?? null);

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

    public function setDependencies()
    {
        /**
         * Documentation: https://getdatepicker.com/4/.
         */
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
        function getDateConfig()
        {
            return {
                useCurrent: false,
                icons: {
                    type: "icons",
                    time: "fa fa-clock",
                    date: "fa fa-calendar",
                    up: "fa fa-arrow-up",
                    down: "fa fa-arrow-down",
                    previous: "fa fa-arrow-left",
                    next: "fa fa-arrow-right",
                    today: "fa fa-calendar-check",
                    clear: "fa fa-trash",
                    close: "fa fa-xmark"
                },
            };
        }

        $(function() {
            $(".datetime-picker").datetimepicker({format: "'.$this->pickerFormatDateTime.'", ...getDateConfig()});
            $(".date-picker").datetimepicker({format: "'.$this->pickerFormatDate.'", ...getDateConfig()});
            $(".time-picker").datetimepicker({format: "'.$this->pickerFormatTime.'", ...getDateConfig()});
        });
        ',
            'datetime'
        );

        Doc::addJs(
            '
        // Date and DateTimePicker
        $(function() {
            $(".datetime-picker").datetimepicker({format: "'.$this->pickerFormatDateTime.'", ...getDateConfig()});
            $(".date-picker").datetimepicker({format: "'.$this->pickerFormatDate.'", ...getDateConfig()});
            $(".time-picker").datetimepicker({format: "'.$this->pickerFormatTime.'", ...getDateConfig()});
        });
        ',
            'datetime',
            'multiple_after_add'
        );
    }

    public function applyFilter($query, $operator = '=')
    {
        if ($this->value !== null) {
            $this->value = DTConverter::toDb($this->value, $this->dbFormat, $this->isConvertToLocal);

            $query->where($this->getAlias().$this->dbField, $operator, $this->value);
        }
    }

    public function getFilterHTML()
    {
        $this->setDependencies();

        $data['input'] = (object) [
            'type' => 'single',
            'column' => $this->dbField,
            'rawValue' => $this->value,
            'value' => old($this->dbField, $this->value),
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
        ];

        return $this->htmlParentDivFilter(\view('form-tool::form.input_types.datetime', $data)->render());
    }

    private function getDateFromExcel($value)
    {
        try {
            // If it's a DateTime object already
            if ($value instanceof \DateTime) {
                return $value->format('Y-m-d');
            }

            // If it's a numeric date (Excel stores it as a number)
            if (is_numeric($value)) {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($value)->format('Y-m-d');
            }

            // If it's a string like 05-Jun-2025, 05-06-2025
            $formats = ['d-M-Y', 'd-m-Y'];
            foreach ($formats as $format) {
                try {
                    return \Carbon\Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Exception $e) {
                    // Try next format
                }
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
