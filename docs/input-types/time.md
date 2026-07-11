# Time Input

Use `time()` for time-only values.

```php
$input->time('startTime', 'Start Time')
    ->required();
```

## Common Uses

- timetable periods
- office hours
- event start/end times

Use `datetime()` when the date and time must be stored together.
