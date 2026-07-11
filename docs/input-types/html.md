# HTML Input

Use `html()` to insert markup into the generated form without creating a normal saved DB field.

```php
$input->html('<hr>');
```

Optional DB field:

```php
$input->html('<div class="alert alert-info">Read only note</div>', 'noticeBlock');
```

## Common Uses

- section dividers
- inline instructions
- layout-only form markup

Use `html()` instead of creating duplicate fake DB fields.
