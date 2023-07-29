<?php

namespace Deep\FormTool\Core\InputTypes\Common;

enum CrudState: string
{
    case NONE = 'none';
    case INDEX = 'index';
    case CREATE = 'create';
    case STORE = 'store';
    case EDIT = 'edit';
    case UPDATE = 'update';
    case DELETE = 'delete';
    case DESTROY = 'destroy';
    case IMPORT = 'import';
}
