# DateTime Input

Use `datetime()` when the field needs both date and time.

```php
$input->datetime('publishedAt', 'Published At');
```

## Common Uses

- publish timestamps
- scheduled events
- expiry dates with time

Add explicit validation if the field must be after or before another timestamp.
