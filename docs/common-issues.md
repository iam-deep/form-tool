# Common Issues

## Field "x" Not Found In The BluePrint

The field was modified, filtered, listed, validated, or updated but was never added to the `BluePrint`.

Fix:

```php
$input->text('x', 'X');
```

Or remove the reference from `modify()`, `list()`, `filter()`, `unique()`, or `updatePostData()`.

## Duplicate Field Found

The same DB field was added twice.

Fix by removing one definition or using `html()` for markup that does not need a DB field.

```php
$input->html('<hr>');
```

## Dependent Select Is Empty

Check:

- the parent field exists in the same BluePrint
- `depend('parentField', 'foreign_key_column')` uses the correct DB column
- `CrudRoute::resource()` registered the `get-options` route
- the parent field has a selected value

## File Does Not Upload

Check:

- the form has `enctype="multipart/form-data"` in the FormTool view
- the field uses `file()` or `image()`
- `path()` is configured
- file size is below `maxUploadSize()` and `config('form-tool.maxFileUploadSize')`
- mime is allowed by `accept()` or the FormTool config

## Old Image Or File Remains After Update

FormTool deletes old files only after a successful update when the stored value changes. If custom save logic bypasses `beforeUpdate()` or `afterUpdate()`, the cleanup hook will not run.

## Multiple Select Stores JSON

This is expected:

```php
$input->select('classIds', 'Classes')->multiple();
```

Use a relation table with `saveAt()` if the values should not be stored as JSON.

## Field Should Show But Not Save

Use:

```php
$this->crud->doNotSave('fieldName');
```

## Field Should Save But Not Come From User Input

Add the field to the BluePrint, then override it:

```php
$this->crud->modify(function (BluePrint $input) {
    $input->text('computedValue');
});

$this->crud->updatePostData([
    'computedValue' => $value,
]);
```

## Disabled Field Does Not Submit

This is normal HTML behavior. If a disabled field must still save, keep a hidden field with the same value or use `updatePostData()` server-side.
