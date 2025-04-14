<?php

namespace Deep\FormTool\Core\Enums;

enum ActionLoggerEnum: string
{
    case CREATE = 'create';
    case DUPLICATE = 'duplicate';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case DESTROY = 'destroy';
    case RESTORE = 'restore';
}
