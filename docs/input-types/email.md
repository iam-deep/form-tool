# Email Input

Use `email()` for email addresses.

```php
$input->email('studentEmail', "Student's Email")
    ->validations(['max:120']);
```

## Behavior

`email()` creates a text field with HTML input type `email` and adds Laravel email validation.

## Notes

- In the current package source, the validation rule is `email:dns`.
- Add `required()` when the email cannot be blank.
