<?php

namespace Deep\FormTool\Core;

abstract class FormStatus
{
    public const CREATE = 1;
    public const STORE = 2;
    public const EDIT = 3;
    public const UPDATE = 4;
    public const DELETE = 5;
    public const DESTROY = 6;
}
