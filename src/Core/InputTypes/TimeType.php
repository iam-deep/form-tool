<?php

namespace Biswadeep\FormTool\Core\InputTypes;

class TimeType extends DateTimeType
{
    public int $type = InputType::Time;
    public string $typeInString = 'time';

    protected $dbFormat = 'H:i:s';
    protected $niceFormat = 'h:i A';
    protected $pickerFormatTime = 'hh:mm A';

    public function __construct()
    {
        $this->niceFormat = \trim(config('form-tool.formatTime', $this->niceFormat));

        $this->classes[] = 'time-picker';

        // This style is specific to this time picker plugin for the multiple table times to work properly
        $this->inlineCSS = 'style="position:relative"';
    }
}
