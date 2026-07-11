# Number Input

Use `number()` for numeric values.

```php
$input->number('roll', 'Roll No.')
    ->required()
    ->validations(['min:1']);
```

## Behavior

`number()` creates a `TextType` configured as number input and automatically adds numeric validation.

## Example

```php
$input->number('aadhaar', 'Aadhaar No.')
    ->validations(['digits:12']);
```

Use `text()` instead when leading zeroes or non-numeric formatting must be preserved.
