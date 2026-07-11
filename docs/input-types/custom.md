# Custom Input

Use `custom()` when a project field needs custom rendering, option loading, formatting, or save behavior.

```php
$input->custom(\App\Http\InputTypes\InputClasses::class)
    ->table('student_sessions', 'ss')
    ->required();
```

## Requirements

A custom input class must:

- extend `Deep\FormTool\Core\InputTypes\BaseInputType`
- implement `Deep\FormTool\Core\InputTypes\Common\ICustomType`

```php
use Deep\FormTool\Core\InputTypes\BaseInputType;
use Deep\FormTool\Core\InputTypes\Common\ICustomType;

class InputClasses extends BaseInputType implements ICustomType
{
    // Implement rendering and behavior required by the field.
}
```

## When To Use

Use a custom input when existing types cannot express the field cleanly. Prefer built-in types first because they already support validation, table rendering, action logging, imports, exports, and save hooks.
