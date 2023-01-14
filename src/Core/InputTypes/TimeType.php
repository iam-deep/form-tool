<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Support\DTConverter;

class TimeType extends DateTimeType
{
    public int $type = InputType::TIME;
    public string $typeInString = 'time';

    protected $pickerFormatTime = 'hh:mm A';

    public function __construct()
    {
        $this->dbFormat = DTConverter::$dbFormatTime;
        $this->niceFormat = DTConverter::$niceFormatTime;

        $this->classes[] = 'time-picker';
        $this->placeholder('Click to select time');
        $this->setFilterOptions(['range']);

        // This style is specific to this time picker plugin for the multiple table times to work properly
        $this->inlineCSS = 'style="position:relative"';
    }
}
