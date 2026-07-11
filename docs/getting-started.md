# Getting Started

FormTool is a Laravel CRUD/form helper used throughout ClassUnify. A controller describes a module once, then FormTool reuses that definition for create/edit forms, validation, saving, list tables, filters, search, buttons, uploads, imports, exports, and activity logging.

## Main Classes

| Class | Purpose |
| --- | --- |
| `Deep\FormTool\Core\Doc` | Entry point. Creates and stores the current `Crud` instance. |
| `Deep\FormTool\Core\Crud` | Main facade for form, table, model, options, and save actions. |
| `Deep\FormTool\Core\BluePrint` | Field builder used inside `Doc::create()`. |
| `Deep\FormTool\Core\Form` | Builds form HTML, validates requests, saves, updates, deletes, and fires events. |
| `Deep\FormTool\Core\Table` | Builds list pages, filters, quick filters, search, pagination, and bulk actions. |
| `Deep\FormTool\Core\DataModel` | Defines direct table metadata when no FormTool model class is used. |
| `Deep\FormTool\Core\InputTypes\*` | Input builders such as text, select, image, file, checkbox, date, and custom fields. |
| `Deep\FormTool\Support\CrudRoute` | Registers CRUD, search, bulk-action, and dependent-select option routes. |

## Mental Model

A normal module has three layers:

1. The controller defines `$title`, `$route`, and `$singularTitle`.
2. The controller creates a `Crud` object with `Doc::create($this, $model, $callback)`.
3. The callback receives a `BluePrint` and adds fields.

The same field definition is reused by:

- create and edit forms
- request validation
- DB save/update data
- default list columns
- filters and search
- action logger values
- file/image upload and cleanup

## Minimal Controller

```php
<?php

namespace App\Http\Controllers\Admin\Setup;

use App\Http\Controllers\Admin\AdminController;
use App\Models\Setup\Categories;
use Deep\FormTool\Core\BluePrint;
use Deep\FormTool\Core\Doc;

class CategoriesController extends AdminController
{
    public $title = 'Categories';
    public $route = 'categories';
    public $singularTitle = 'Category';

    protected $crud = null;

    public function setup()
    {
        $this->crud = Doc::create($this, Categories::class, function (BluePrint $input) {
            $input->text('categoryName', 'Category Name')->required();
            $input->select('status', 'Status')
                ->options([1 => 'Active', 0 => 'Inactive'])
                ->noFirst()
                ->default(1);
        });
    }
}
```

## Standard Page Rendering

```php
public function index(Request $request)
{
    $this->setup();
    $this->createList();

    $data['title'] = $this->title;
    $data['page'] = $this->crud->index();

    return $this->render('form-tool::list.index', $data);
}

public function create(Request $request)
{
    $this->setup();

    $data['title'] = 'Add '.$this->singularTitle;
    $data['page'] = $this->crud->create();

    return $this->render('form-tool::form.index', $data);
}

public function store(Request $request)
{
    $this->setup();

    return $this->crud->store();
}
```

## Route Registration

```php
use Deep\FormTool\Support\CrudRoute;

CrudRoute::resource('categories', CategoriesController::class);
```

This registers the normal resource routes plus:

- `GET categories/search`
- `POST categories/bulk-action`
- `POST categories/get-options`

The `get-options` route matters for dependent selects.
