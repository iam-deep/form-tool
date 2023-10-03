<?php

namespace Deep\FormTool\Core;

enum EventType: int
{
    case ALL = 0;
    case CREATE = 1;
    case DUPLICATE = 2;
    case UPDATE = 3;
    case DELETE = 4;
    case DESTROY = 5;
    case RESTORE = 6;
}
