# Password Input

Use `password()` for password fields.

```php
$input->password('password', 'Password')
    ->required()
    ->validations(['min:8']);
```

## Notes

- Do not show password fields in list tables.
- Hashing should be handled by the module save logic, model mutator, or a FormTool hook.
- Use `doNotSave()` if the field is only used to confirm or trigger separate password handling.
