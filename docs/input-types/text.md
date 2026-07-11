# Text Input

Use `text()` for normal string values.

```php
$input->text('studentName', 'Student Name')
    ->required()
    ->validations(['max:100']);
```

## Common Uses

- names
- codes
- short labels
- phone numbers that should not be treated as arithmetic numbers

## Notes

- The field saves the submitted value as-is unless another hook changes it.
- Use `validations()` for length, uniqueness, format, or custom Laravel rules.
- Use `table($tableName, $alias)` when the field is shown from a joined table in a list.
