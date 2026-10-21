# Text length validation

Default maximums apply when no explicit `max:` rule is supplied:

| Input | Default maximum |
| --- | --- |
| `text()` | 250 characters |
| `textarea()` | 500 characters |
| `editor()` | 65,500 bytes of stored HTML-encoded content, below the SQL `TEXT` limit |

There is no default minimum. Fields remain optional unless `required()` or a required validation rule is supplied. The plain text default does not apply to numeric or email inputs.

Use `min()` and `max()` for explicit character limits:

```php
$input->text('title')->required()->min(3)->max(150);
$input->textarea('summary')->max(1000);
$input->editor('body')->max(100000); // Requires a column larger than TEXT.
```

Limits can also be supplied through `validations()` as arrays or pipe-delimited strings:

```php
$input->text('title')->validations(['string', 'min:3', 'max:300']);
$input->textarea('summary')->validations('nullable|min:10|max:2000');
```

For each bound, the last explicitly configured rule wins, regardless of which method supplies it:

```php
$input->text('title')->max(100)->validations(['max:300']); // Maximum 300.
$input->text('title')->validations('max:300')->max(100);   // Maximum 100.
```

Calling `validations(..., replace: true)` discards previously configured explicit rules, as before. If the replacement has no maximum, the input's default maximum applies. Custom validation messages and unrelated validation rules are preserved. Limits must be non-negative integers.

For editors, `min()` and explicit `max()` rules count submitted characters, including HTML markup. An explicit maximum replaces both default editor checks. Without an explicit maximum, validation also checks the byte length after the same HTML encoding and URL normalization used for persistence. This accounts for UTF-8 characters and HTML entities; `max:65500` alone would not protect a SQL `TEXT` column from overflowing.
