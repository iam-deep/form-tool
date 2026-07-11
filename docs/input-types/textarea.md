# Textarea Input

Use `textarea()` for multi-line plain text.

```php
$input->textarea('address', 'Address')
    ->validations(['max:500']);
```

## Common Uses

- addresses
- remarks
- descriptions that do not need rich formatting

## Notes

Use `editor()` instead when the field should allow rich text formatting.
