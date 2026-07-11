# Multiple Rows

Use `multiple()` for repeating row groups.

```php
$input->multiple('signatures', 'Signatures', function (BluePrint $row) {
    $row->select('type', 'Type')
        ->options(['principal' => 'Principal', 'other' => 'Other'])
        ->required();

    $row->text('designation', 'Designation')->required();
    $row->image('signature', 'Signature')->path('settings');
})->required(1)->confirmBeforeDelete();
```

By default, repeated rows are saved as JSON into the parent field.

## Table Backed Rows

```php
$input->multiple('items', 'Items', function (BluePrint $row) {
    $row->select('itemId', 'Item')->options('items.itemId.itemName')->required();
    $row->number('qty', 'Qty')->required();
})
->table('invoice_items', 'itemRowId', 'invoiceId', 'sortOrder')
->orderable('sortOrder')
->keepId();
```

Table-backed rows are inserted/updated in the child table using the parent row ID as the foreign key.

## Multiple-Specific Methods

```php
->required(1)
->help('Add at least one row')
->confirmBeforeDelete()
->table('invoice_items', 'itemRowId', 'invoiceId', 'sortOrder')
->orderable('sortOrder')
->keepId()
```

## Notes

- `required()` on a multiple group receives the required number of rows.
- `keepId()` must be called after `table()`.
- If `orderable()` is used with `keepId()`, pass the order column name to `orderable()`.
