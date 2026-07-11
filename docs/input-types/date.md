# Date Input

Use `date()` for date-only values.

```php
$input->date('dob', 'Date of Birth')
    ->required();
```

## Filters

Date fields can be used with list filters:

```php
$this->crud->list()->filter([
    'dateOfAdmission' => 'range',
]);
```

Supported modes include `gt`, `lt`, and `range`.
