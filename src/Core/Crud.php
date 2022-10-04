<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\URL;

class Crud
{
    public string $name;

    private $resource;
    private DataModel $model;
    private BluePrint $bluePrint;
    private Form $form;
    private Table $table;

    public $isSoftDelete = true;

    public function create(object $resource, $model, Closure $callback, $name = 'default')
    {
        $this->resource = $resource;
        $this->name = $name;

        if ($model instanceof DataModel) {
            $this->model = $model;
        } else {
            $this->model = new DataModel($model);
        }

        $this->bluePrint = new BluePrint();
        $callback($this->bluePrint);

        $this->form = new Form($this->resource, $this->bluePrint, $this->model);
        $this->form->setCrud($this);

        $this->table = new Table($this->resource, $this->bluePrint, $this->model);
        $this->table->setCrud($this);

        $this->isSoftDelete = \config('form-tool.isSoftDelete', true);
        $this->model->softDelete($this->isSoftDelete);

        return $this;
    }

    public function modify(Closure $callback)
    {
        $callback($this->bluePrint);

        return $this;
    }

    public function run()
    {
        $response = $this->form->init();

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return $response->send();
        }

        return $this;
    }

    public function db($tableName, $primaryId = '', $token = '', $orderBy = '', $foreignKey = '')
    {
        $this->model->db($tableName, $primaryId, $token, $orderBy, $foreignKey);

        return $this;
    }

    public function softDelete(bool $enable = true)
    {
        $this->isSoftDelete = $enable;
        $this->model->softDelete($enable);

        return $this;
    }

    //region FormGetter

    /*public function getHTMLForm()
    {
        return $this->form->getHTMLForm();
    }

    public function store()
    {
        return $this->form->store();
    }

    public function edit($id = null)
    {
        return $this->form->edit($id);
    }

    public function update($id = null)
    {
        return $this->form->update($id);
    }

    public function destroy($id = null)
    {
        return $this->form->destroy($id);
    }*/

    //endregion

    public function __call($method, $parameters)
    {
        /*if (\method_exists($this->form, $method)) {
            $this->form->{$method}(...$parameters);
        } else if (\method_exists($this->model, $method)) {
            $this->model->{$method}(...$parameters);
        } else if (\method_exists($this->table, $method)) {
            $this->table->{$method}(...$parameters);
        } else {
            throw new \BadMethodCallException("$method not found in class Crud.");
        }*/

        return $this->form->{$method}(...$parameters);
    }

    //region TableMethod

    public function getTableContent()
    {
        return $this->table->getContent();
    }

    public function getTablePagination()
    {
        return $this->table->getPagination();
    }

    public function searchIn($fields)
    {
        $this->table->searchIn($fields);

        return $this;
    }

    public function search()
    {
        return $this->table->search();
    }

    public function bulkAction(Closure $callback = null)
    {
        return $this->table->bulkAction->perform($callback);
    }

    //endregion

    public function createForm()
    {
        return $this->form->create();
    }

    public function getForm()
    {
        return $this->form;
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getModel()
    {
        return $this->model;
    }
}
