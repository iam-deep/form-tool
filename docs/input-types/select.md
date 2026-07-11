# Select Input

Use `select()` for static options, DB-backed options, dependent dropdowns, chosen selects, and multiple select values.

## Static Options

```php
$input->select('gender', 'Gender')
    ->options(['Male' => 'Male', 'Female' => 'Female']);
```

## Database Options

Use the string format:

```text
table.valueColumn.textColumn[.orderByColumn[.orderDirection]]
```

Examples:

```php
$input->select('classId', 'Class')
    ->options('classes.classId.class.classNumeric');

$input->select('sectionId', 'Section')
    ->options('sections.sectionId.section.sortOrder.asc');
```

## Pattern Labels

When the label must combine multiple DB columns:

```php
$input->select('studentId', 'Student')
    ->options('students.studentId.%s - %s', 'studentCode', 'studentName');
```

## First Option

```php
$input->select('status', 'Status')
    ->options([1 => 'Active', 0 => 'Inactive'])
    ->noFirst();

$input->select('guardian', 'Guardian')
    ->options(['Father' => 'Father', 'Mother' => 'Mother'])
    ->first('', ' ');
```

## Multiple Select

```php
$input->select('classIds', 'Classes')
    ->options('classes.classId.class.classNumeric')
    ->multiple()
    ->required();
```

Multiple select values are saved as JSON unless `saveAt()` is used.

## Save Multiple Values In A Relation Table

```php
$input->select('subjectIds', 'Subjects')
    ->options('subjects.subjectId.subject')
    ->multiple()
    ->saveAt('student_subjects', 'studentSubjectId', 'studentId');
```

## Chosen Plugin

```php
$input->select('eligibilityFields', 'Eligibility Form Fields')
    ->options($studentFields)
    ->plugin('chosen')
    ->multiple();
```

`multiple()` automatically enables the chosen plugin.

## Dependent Select

```php
$input->select('currentStateId', 'State')
    ->options('m_states.stateId.state');

$input->select('currentDistrictId', 'District')
    ->options('m_districts.districtId.district')
    ->depend('currentStateId', 'stateId');
```

The `get-options` route registered by `CrudRoute::resource()` reloads the child options.

Use `dependParent()` inside a multiple table when the dependency is on the parent form.

## Quick Add

`quickAdd($controllerClass)` is available for DB-backed options. The option source must be database-backed, and the user must have create permission for the quick-add controller route.
