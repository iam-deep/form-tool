# Common Input Parameters And Functions

Most input types extend `BaseInputType`, so they share common chain methods.

## Builder Signature

Most fields use:

```php
$input->text(string $dbField, ?string $label = null);
```

The first argument is the DB/request field name. The second argument is the human label. If the label is omitted, FormTool derives one from the DB field.

## Common Chain Methods

```php
->label('New Label')
->required()
->required(false)
->default($value)
->placeholder('Enter value')
->help('Shown below the field')
->validations(['max:255'])
->validations(['required'], ['required' => 'Custom message'], replace: true)
->raw('autocomplete="off"')
->readonly()
->disabled()
->addClass('my-class')
->removeClass('my-class')
->addStyle('width:120px;')
->table('student_sessions', 'ss')
->logColumn(false)
->setJson()
->importSample('Example value')
```

## Validation

Each field contributes Laravel validation rules.

```php
$input->text('studentName', 'Student Name')
    ->required()
    ->validations(['max:100']);
```

Custom validation callbacks are also supported in Laravel rule arrays.

```php
$input->number('roll', 'Roll No.')
    ->validations([
        function ($attribute, $value, $fail) {
            if ($value < 0) {
                $fail('Roll No. cannot be less than 0.');
            }
        },
    ]);
```

Use `callbackValidation()` for module-level validation.

```php
$this->crud = Doc::create($this, $model, function (BluePrint $input) {
    $input->select('classIds', 'Class')->options('classes.classId.class')->multiple()->required();
})->callbackValidation(function ($request, $type) {
    if ($type === 'store' && empty($request->classIds)) {
        return 'Please select at least one class.';
    }

    return true;
});
```

Return `true` when valid, or an error message when invalid.

## Modify Existing Fields

Use `modify()` when create/edit/store/update logic needs a small change without duplicating the whole setup.

```php
$this->crud->modify(function (BluePrint $input) use ($classId) {
    $input->modify('classId')->default($classId)->disabled();
});
```

Remove a field from the BluePrint:

```php
$this->crud->modify(function (BluePrint $input) {
    $input->remove('studentCode');
});
```

## Important Behavior

- `required()` adds Laravel `required` validation and usually adds the HTML `required` attribute.
- `required(false)` removes the required rule and raw required attribute.
- `placeholder()` on normal inputs writes a raw `placeholder="..."` attribute.
- `placeholder()` on `select()` writes `data-placeholder="..."` for the chosen plugin.
- `disabled()` fields do not submit in normal HTML. Add a hidden field if the value must still be saved.
- `logColumn(false)` removes that field from action logger output.
- `setJson()` JSON-encodes array values in `beforeStore()` and `beforeUpdate()`.
