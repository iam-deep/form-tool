# CRUD

FormTool CRUD starts with `Doc::create($controller, $model, $callback)`. The callback receives a `BluePrint` and defines fields.

```php
$this->crud = Doc::create($this, Students::class, function (BluePrint $input) {
    $input->text('studentName', 'Student Name')->required();
    $input->select('gender', 'Gender')->options(['Male' => 'Male', 'Female' => 'Female']);
});
```

## List Definition

If no list is defined, FormTool can build a default table from fields. Most real modules customize it.

```php
protected function createList()
{
    $this->crud->list(function ($table) {
        $table->bulkActionCheckbox();
        $table->slNo();
        $table->default('studentCode');
        $table->default('studentName');
        $table->default('image');
        $table->actions(['view', 'edit', 'delete']);
    });
}
```

Useful table methods:

```php
$table->default('studentName');
$table->text('fatherMobile', 'Father Mobile');
$table->image('image', 'Photo');
$table->any('%s - %s', 'classId', 'sectionId');
$table->any(fn ($row) => '<b>'.$row->name.'</b>');
$table->actions(['view', 'edit', 'delete']);
```

## Search And Filters

```php
$this->crud->searchIn(['studentName', 'studentCode', 'fatherMobile']);
```

```php
$this->crud->list()->filter([
    'classId',
    'sectionId',
    'dateOfAdmission' => 'range',
]);
```

Supported date/time filter modes include:

```php
'dateOfAdmission' => 'gt'
'dateOfAdmission' => 'lt'
'dateOfAdmission' => 'range'
```

## Buttons And Actions

Page-level buttons:

```php
use Deep\FormTool\Core\Button;

$this->crud->list()->buttons([
    'create',
    Button::make('Import', '/import', 'create')->icon('<i class="fa fa-upload"></i>'),
    Button::make('Export', '/export')->icon('<i class="fa fa-download"></i>'),
]);
```

Row action buttons:

```php
$table->actions([
    'view',
    'edit',
    Button::make('Activities', createUrl('activities-log?module=Students&id={id}'), 'view', 'activities-log'),
    'delete',
]);
```

Useful placeholders:

| Placeholder | Meaning |
| --- | --- |
| `{id}` | Current row ID. |
| `{crud_name}` | Current CRUD name. |
| `{crud_url}` | Base CRUD URL. |
| `{query_string}` | Current query string. |

Buttons respect FormTool guards:

```php
Button::make('Admission Settings', createUrl('admission-settings'), 'view', 'admission-settings');
```

## Saving Flow

On store/update, `Form` generally does this:

1. Build validation rules from the BluePrint.
2. Run field `beforeValidation()` hooks.
3. Validate the request.
4. Convert each field value through `beforeStore()` or `beforeUpdate()`.
5. Save using the configured model.
6. Run `afterStore()` or `afterUpdate()`.
7. Save multiple table rows.
8. Save action logs.
9. Invoke registered events.

## Save Controls

Render/validate a field but skip persistence:

```php
$this->crud->doNotSave(['temporaryNote']);
```

Persist only specific fields:

```php
$this->crud->saveOnly(['name', 'status']);
```

Override processed values before saving:

```php
$this->crud->updatePostData([
    'formFields' => json_encode(NewStudentFields::normalizeFormFields($request->post('permission'))),
]);
```

Direct save methods for service/API flows:

```php
$this->crud->getForm()->directStore();
$this->crud->getForm()->directUpdate($id);
$this->crud->getForm()->directDestroy($id);
```

## Events

```php
use Deep\FormTool\Core\EventType;

$this->crud->onEvent(EventType::CREATE, function ($id, object $data, EventType $event) {
    // Run code after create.
});

$this->crud->onEvent([EventType::UPDATE, EventType::DELETE], function ($id, object $data, EventType $event) {
    // Run code after update or delete.
});
```

The callback receives the saved/deleted ID, saved/deleted data, and the `EventType`.
