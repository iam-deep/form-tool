<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Support\DTConverter;

class DateTimeType extends BaseDateTimeType
{
    public int $type = InputType::DATE_TIME;
    public string $typeInString = 'datetime';

    protected $pickerFormatDateTime = 'DD-MM-YYYY hh:mm A';

    public function __construct()
    {
        parent::__construct();

        $this->dbFormat = DTConverter::$dbFormatDateTime;
        $this->niceFormat = DTConverter::$niceFormatDateTime;

        $this->classes[] = 'datetime-picker';
        $this->placeholder('Click to select date and time');
        $this->setFilterOptions(['range']);
    }
}
