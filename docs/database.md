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
