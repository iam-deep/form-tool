<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

abstract class InputType
{
    // TextFields
    public const Text = 0;
    public const Number = 1;
    public const Email = 2;
    public const Password = 3;
    public const Hidden = 4;

    // Radio & Checkbox
    public const Radio = 10;
    public const Checkbox = 11;

    // Dropdown
    public const Select = 20;

    // File Browser
    public const Image = 30;
    public const File = 31;

    // DateTime
    public const Date = 40;
    public const Time = 41;
    public const DateTime = 42;

    // Textare & Editor
    public const Textarea = 50;
    public const Editor = 51;

    // Custom
    public const Custom = 99;
}
