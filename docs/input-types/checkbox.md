# Checkbox Input

Use `checkbox()` for boolean or multi-value choices.

## Single Checkbox

A single checkbox stores `1` when checked and `0` when unchecked by default.

```php
$input->checkbox('sameAddress', ' ')
    ->captions('Permanent Address Same as Current Address')
    ->default(1);
```

Custom values:

```php
$input->checkbox('isPublished', 'Published')
    ->captions('Published', 'Draft')
    ->values('yes', 'no');
```

## Multiple Checkboxes

Multiple checkboxes save selected values as JSON.

```php
$input->checkbox('days', 'Days')
    ->options(['mon' => 'Monday', 'tue' => 'Tuesday'])
    ->multiple();
```

## Notes

- Use clear captions when the label is blank or short.
- For relation-table storage, prefer a saveable multi-select with `saveAt()` when that shape fits the UI.
