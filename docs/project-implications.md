# Project Implications

These notes explain how FormTool usage affects this ClassUnify project.

## Keep setup() As Source Of Truth

The `setup()` method should remain the source of truth for fields. Avoid duplicating field definitions across create/edit/list code. Use `modify()` when a field needs state-specific changes.

```php
$this->crud->modify(function (BluePrint $input) use ($classId) {
    $input->modify('classId')->default($classId)->disabled();
});
```

## Prefer Existing FormTool Choke Points

For FormTool-backed modules, prefer:

- field definitions in `setup()`
- `modify()` for state-specific field changes
- `updatePostData()` for converting request shape before save
- `callbackValidation()` for cross-field validation
- `onEvent()` for post-save behavior

This keeps generated forms, validation, saving, list rendering, and logs aligned.

## Admission Setup Pattern

`AdmissionFormSetupController` renders a FormTool form inside a custom page and uses `updatePostData()` to store setup JSON.

```php
$this->crud->modify(function ($input) {
    $input->text('formFields');
    $input->text('documentFields');
});

$this->crud->updatePostData([
    'formFields' => json_encode(NewStudentFields::normalizeFormFields($request->post('permission'))),
    'documentFields' => json_encode($request->post('documentPermission')),
]);

return $this->crud->store();
```

Use this style when the UI posts a complex shape but the DB stores normalized JSON.

## File And Image Inputs

Let FormTool file/image inputs handle upload, validation, thumbnail display, and old-file cleanup unless the module has a strong reason to own that pipeline.

When custom save logic is required, preserve the file field markers that FormTool expects. Otherwise update/delete cleanup can be skipped.

## Permissions And Buttons

FormTool buttons can include guard information:

```php
Button::make('Admission Settings', createUrl('admission-settings'), 'view', 'admission-settings');
```

Preserve these route and action names when changing UI buttons. They affect visibility and permission checks.

## Best Practices

- Keep field definitions in one place.
- Use `DataModel` when a table does not have a dedicated FormTool model.
- Register modules through `CrudRoute::resource()` so search, bulk action, and dependent select routes exist.
- Use `doNotSave()` for UI-only fields.
- Use `updatePostData()` when request data needs conversion before save.
- Use `saveAt()` for relation-table selections instead of storing JSON when the database design needs rows.
- Prefer existing project custom input types in `app/Http/InputTypes` before creating new ones.
