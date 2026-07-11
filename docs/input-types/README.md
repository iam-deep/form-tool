# Input Types

Each FormTool input type is built inside a `BluePrint` callback.

```php
$this->crud = Doc::create($this, Students::class, function (BluePrint $input) {
    $input->text('studentName', 'Student Name')->required();
});
```

## Pages

- [Text](text.md)
- [Number](number.md)
- [Email](email.md)
- [Password](password.md)
- [Hidden](hidden.md)
- [Textarea](textarea.md)
- [Date](date.md)
- [Time](time.md)
- [DateTime](datetime.md)
- [Editor](editor.md)
- [HTML](html.md)
- [Checkbox](checkbox.md)
- [Select](select.md)
- [File](file.md)
- [Image](image.md)
- [Multiple Rows](multiple.md)
- [Custom](custom.md)

Shared chain methods are documented in [Common Input Parameters And Functions](../common-input-parameters.md).
