<?php

namespace Deep\FormTool\Core\InputTypes\Common;

abstract class InputType
{
    // TextFields
    public const TEXT = 0;
    public const NUMBER = 1;
    public const EMAIL = 2;
    public const PASSWORD = 3;
    public const HIDDEN = 4;

    // Radio & Checkbox
    public const RADIO = 10;
    public const CHECKBOX = 11;

    // Dropdown
    public const SELECT = 20;

    // File Browser
    public const IMAGE = 30;
    public const FILE = 31;

    // DateTime
    public const DATE = 40;
    public const TIME = 41;
    public const DATE_TIME = 42;

    // Textare & Editor
    public const TEXTAREA = 50;
    public const EDITOR = 51;

    // Custom
    public const CUSTOM = 99;
}
