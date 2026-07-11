# Hidden Input

Use `hidden()` when the form must submit a value that should not be edited directly.

```php
$input->hidden('parentId');
```

## Common Uses

- IDs passed from page state
- fixed class/session values
- computed values that are filled through `default()` or `updatePostData()`

## Example

```php
$input->hidden('classId')->default($classId);
```

Hidden fields are still user-submitted data. Validate or override important values server-side when they affect permissions or ownership.
