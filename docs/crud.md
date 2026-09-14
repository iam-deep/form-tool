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

Link buttons append the current page's query string by default. Disable this for
links that should keep only their explicitly configured parameters:

```php
Button::make('Activities', createUrl('activities-log?module=Students&id={id}'), 'view', 'activities-log')
    ->withQueryString(false);
```

Placeholders such as `{id}` are still replaced.

## Quick Filters

Add tabs between **All** and **Trash** using `quickFilter()` on the CRUD or the
Table returned by `list()`:

```php
$this->crud
    ->quickFilter('active', 'Active', ['status' => 1], default: true)
    ->quickFilter('inactive', 'Inactive', ['status' => 0])
    ->quickFilter('pending', 'Pending', ['status' => 2]);
```

Use your module's actual status values. Each tab displays its own count. The
default applies when `quick_status` is absent; clicking All explicitly overrides
it. If several filters specify `default: true`, the last one wins. Without a
default, All remains selected. Unknown selections fall back to All.

For custom conditions, pass a callback receiving the query and `DataModel`:

```php
$this->crud->quickFilter('pending', 'Pending', function ($query, $model) {
    $query->whereNull($model->getAlias().'approvedAt');
});
```

Conditions are grouped and apply to normal lists, search, and pagination alongside
regular filters. Counts follow the existing All/Trash behavior and exclude regular
filters and search terms. Soft-deleted rows are excluded from custom tabs when
soft deletion is enabled; Trash retains its existing permission requirement.
Direct `?id=...` links retain their existing record lookup behavior.
Keys must start with a letter and contain only letters, numbers, underscores or
hyphens; `all`, `trash` and `filtered` are reserved. Registering the same key replaces its
definition. These options configure list views, not access permissions.

When the regular filter form has an applied value, a **Filtered** tab automatically
appears after the status tabs and becomes active. Its count includes the selected
status and regular filters across all pages, independently of keyword search.
The link retains these filters and opens their first page. Empty filters, status
selection alone, sorting, pagination and direct ID lookups do not show this tab.

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
