<?php

namespace Biswadeep\FormTool\Core\InputTypes;

class DateType extends DateTimeType
{
    public int $type = InputType::Date;
    public string $typeInString = 'date';

    protected $dbFormat = 'Y-m-d';
    protected $niceFormat = 'd-m-Y';
    protected $pickerFormatDate = 'DD-MM-YYYY';

    public function __construct()
    {
        $this->niceFormat = \trim(config('form-tool.formatDate', $this->niceFormat));

        $this->classes[] = 'date-picker';

        // This style is specific to this date picker plugin for the multiple table dates to work properly
        $this->inlineCSS = 'style="position:relative"';
    }
}
