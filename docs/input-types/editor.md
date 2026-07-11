# Editor Input

Use `editor()` for rich text content.

```php
$input->editor('description', 'Description');
```

## Common Uses

- long formatted notices
- page content
- detailed descriptions

## Notes

- Validate length when the DB column has a limit.
- Sanitize or restrict output at render time when content is user-generated.
- Use `textarea()` when plain text is enough.
