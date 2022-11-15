<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Support\DTConverter;

class DateType extends DateTimeType
{
    public int $type = InputType::Date;
    public string $typeInString = 'date';

    protected $pickerFormatDate = 'DD-MM-YYYY';

    public function __construct()
    {
        $this->dbFormat = DTConverter::$dbFormatDate;
        $this->niceFormat = DTConverter::$niceFormatDate;

        $this->classes[] = 'date-picker';
        $this->placeholder('Click to select date');
        $this->setFilterOptions(['range']);

        // This style is specific to this date picker plugin for the multiple table dates to work properly
        $this->inlineCSS = 'style="position:relative"';

        $this->isConvertToLocal = false;
    }
}
