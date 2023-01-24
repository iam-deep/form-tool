<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Support\DTConverter;

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
        $this->setFilterOptions(['range']);

        $this->isConvertToLocal = false;
    }
}
