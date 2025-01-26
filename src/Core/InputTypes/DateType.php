<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Support\DTConverter;

class DateType extends BaseDateTimeType
{
    public int $type = InputType::DATE;
    public string $typeInString = 'date';

    protected $pickerFormatDate = 'DD-MM-YYYY';

    public function __construct()
    {
        parent::__construct();

        $this->dbFormat = DTConverter::$dbFormatDate;
        $this->niceFormat = DTConverter::$niceFormatDate;

        $this->classes[] = 'date-picker';
        $this->placeholder('Click to select date');
        $this->setFilterOptions(['range', 'gt', 'lt']);

        $this->isConvertToLocal = false;
    }

    public function getExportValue($value)
    {
        if (! $value) {
            return null;
        }

        return date('d-M-Y', strtotime($value));
    }
}
