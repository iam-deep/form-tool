<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\URL;

class Crud
{
    public string $name;

    private $resource;
    private $model;
    private $bluePrint;
    private $form;
    private $table;

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

    public function db($tableName, $primaryId = '', $orderBy = '', $foreignKey = '')
    {
        $this->model->db($tableName, $primaryId, $orderBy, $foreignKey);

        return $this;
    }

    public function softDelete(bool $enable = true)
    {
        $this->isSoftDelete = $enable;
        $this->model->softDelete($enable);

        return $this;
    }

    //region FormMethods

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
    }

    public function doNoSave($fields)
    {
        $this->form->doNotSave();
    }*/

    public function __call($method, $parameters)
    {
        //$class_methods = get_class_methods($this->form);

        return $this->form->{$method}(...$parameters);

        //return call_user_func_array(array($this->form, $name), $parameters);
    }

    //endregion

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

    //endregion

    public function getForm()
    {
        return $this->form->getForm();
    }

    public function getTable()
    {
        return $this->table;
    }
}
