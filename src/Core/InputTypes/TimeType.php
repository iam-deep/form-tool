<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Support\DTConverter;

class TimeType extends BaseDateTimeType
{
    public int $type = InputType::TIME;
    public string $typeInString = 'time';

    protected $pickerFormatTime = 'hh:mm A';

    public function __construct()
    {
        parent::__construct();

        $this->dbFormat = DTConverter::$dbFormatTime;
        $this->niceFormat = DTConverter::$niceFormatTime;

        $this->classes[] = 'time-picker';
        $this->placeholder('Click to select time');
        $this->setFilterOptions(['range']);
    }

    public function getExportValue($value)
    {
        if (! $value) {
            return null;
        }

        return date('h:i a', strtotime($value));
    }
}
