<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

abstract class InputType
{
    // TextFields
    const Text = 0;
    const Number = 1;
    const Email = 2;
    const Password = 3;
    const Hidden = 4;

    // Radio & Checkbox
    const Radio = 10;
    const Checkbox = 11;

    // Dropdown
    const Select = 20;

    // File Browser
    const Image = 30;
    const File = 31;

    // DateTime
    const Date = 40;
    const Time = 41;
    const DateTime = 42;

    // Textare & Editor
    const Textarea = 50;
    const Editor = 51;

    // Custom
    const Custom = 99;
}
