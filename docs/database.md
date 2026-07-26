# Database

FormTool can use either a model class or a direct `DataModel` definition.

## Model Class

Use a model class when it already extends the expected FormTool model structure and declares the metadata FormTool needs.

```php
$this->crud = Doc::create($this, Students::class, function (BluePrint $input) {
    $input->text('studentName', 'Student Name')->required();
});
```

## Direct DataModel

Use `DataModel` when a table does not have a dedicated FormTool model class.

```php
use Deep\FormTool\Core\DataModel;

$model = new DataModel();
$model->db('admission_form_setup', 'formId');

$this->crud = Doc::create($this, $model, function (BluePrint $input) {
    $input->select('classIds', 'Class')
        ->options('classes.classId.class')
        ->multiple();
});
```

Direct `db()` signature:

```php
$model->db(
    tableName: 'admission_form_setup',
    primaryId: 'formId',
    token: '',
    orderByCol: 'formId',
    foreignKey: ''
);
```

If a token column is configured, FormTool can use token values in URLs while still resolving the primary ID internally.

## Field Table Alias

Use `table()` on a field when the list/search/filter column comes from a joined table or alias.

```php
$input->text('studentName', 'Student Name')
    ->table('students', 's');
```

## Key Value Format

Use `format('keyValue', $groupName)` when a module stores settings as rows like `groupName`, `key`, and `value` instead of normal columns.

```php
$this->crud = Doc::create($this, $model, function (BluePrint $input) {
    $input->text('schoolName', 'School Name');
    $input->image('logo', 'Logo')->path('settings');
})->format('keyValue', 'school');
```

In key-value format:

- soft delete is disabled
- FormTool reads existing rows by `groupName`
- update deletes and recreates rows for that group

## Updates, Soft Deletes, And Restore

Normal updates, soft deletes, restores, and permanent deletes use separate model operations.

`BaseModel::updateOne()` updates active rows only when soft delete is enabled. Use `BaseModel::softDelete()` to mark active rows as deleted, `BaseModel::restore()` to restore deleted rows, and `BaseModel::deleteOne()` to permanently remove a row.

The transition methods accept one ID/token or an array. Arrays are updated in one query, and both methods return the affected-row count:

```php
use Deep\FormTool\Core\Auth;

$deleted = Students::softDelete(
    [10, 11, 12],
    ['deletedBy' => Auth::id(), 'deletedAt' => date('Y-m-d H:i:s')]
);

$restored = Students::restore(
    [10, 11, 12],
    ['deletedBy' => null, 'deletedAt' => null]
);
```

Pass `true` as the third argument when the supplied values belong to the configured token column:

```php
Students::softDelete(['token-a', 'token-b'], $deletionData, true);
Students::restore(['token-a', 'token-b'], $restoreData, true);
```

Passing an empty array returns `0` without executing an update. Soft delete changes active rows only; restore changes deleted rows only.

`DataModel::restore()` provides the same scalar-or-array restore behavior and fills the configured deletion metadata automatically. `DataModel::updateDelete()` remains available as a deprecated compatibility method and delegates to `BaseModel::softDelete()`.

```php
$model->updateDelete([10, 11, 12]); // Deprecated compatibility API
$model->restore([10, 11, 12]);
```

`DataModel::softDelete(bool)` is not a record-deletion operation. It remains the existing switch used to enable or disable soft-delete behavior for a CRUD definition.

## Relation Saves With saveAt

Multiple saveable inputs can write selections into a relation table instead of JSON.

```php
$input->select('subjectIds', 'Subjects')
    ->options('subjects.subjectId.subject')
    ->multiple()
    ->saveAt('student_subjects', 'studentSubjectId', 'studentId');
```

Arguments:

| Argument | Meaning |
| --- | --- |
| `student_subjects` | Relation table name. |
| `studentSubjectId` | Relation table primary key column. |
| `studentId` | Parent reference column; defaults to the current model primary key. |

The `Saveable` trait deletes old relation rows for the parent ID and inserts the selected values again after save.
