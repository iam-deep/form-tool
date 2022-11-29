<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class Crud
{
    public string $name;

    private $resource;
    private DataModel $model;
    private BluePrint $bluePrint;
    private Form $form;
    private Table $table;

    protected string $format = 'default';
    protected string $groupName = 'default';
    protected bool $isSoftDelete = true;

    public function make(object $resource, $model, Closure $callback, string $name = 'default'): Crud
    {
        $this->resource = $resource;
        $this->name = $name;

        if ($model instanceof DataModel) {
            $this->model = $model;
        } else {
            $this->model = new DataModel($model);
        }
        $this->model->setCrud($this);

        $this->bluePrint = new BluePrint();
        $callback($this->bluePrint);

        $this->form = new Form($this->resource, $this->bluePrint, $this->model);
        $this->form->setCrud($this);

        $this->table = new Table($this->resource, $this->bluePrint, $this->model);
        $this->table->setCrud($this);

        $this->softDelete(\config('form-tool.isSoftDelete', true));

        return $this;
    }

    public function modify(Closure $callback): Crud
    {
        $callback($this->bluePrint);

        return $this;
    }

    /**
     * Format of the CRUD. It can de default or store data as key value pair.
     *
     * @param  string  $var  Desired values: (default, keyValue)
     * @return \Biswadeep\FormTool\Core\Crud
     **/
    public function format(string $format = 'default', string $groupName = 'default'): Crud
    {
        $this->format = $format;

        if ($format != 'default') {
            $this->softDelete(false);
            $this->groupName = $groupName;
        }

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

    public function db(string $tableName, ?string $primaryId = '', ?string $token = '', ?string $orderBy = '', ?string $foreignKey = ''): Crud
    {
        $this->model->db($tableName, $primaryId, $token, $orderBy, $foreignKey);

        return $this;
    }

    public function softDelete(bool $enable = true): Crud
    {
        $this->isSoftDelete = $enable;
        $this->model->softDelete($enable);

        return $this;
    }

    public function __call($method, $parameters)
    {
        return $this->form->{$method}(...$parameters);
    }

    private function save()
    {
        if (! \config('form-tool.isPreventForeignKeyDelete')) {
            return;
        }

        $selects = $this->bluePrint->getSelectDbOptions();

        if (! $selects) {
            return;
        }

        $count = DB::table('cruds')->where('route', $this->resource->route)->count();

        $crudData = [
            'route' => $this->resource->route,
            'data' => \json_encode($selects),
        ];
        if ($count > 0) {
            DB::table('cruds')->where('route', $this->resource->route)->update($crudData);
        } else {
            DB::table('cruds')->insert($crudData);
        }
    }

    //region TableOptions

    public function searchIn($fields)
    {
        $this->table->searchIn($fields);

        return $this;
    }

    //endregion

    public function index()
    {
        $request = request();
        $currentUrl = url()->current();

        $this->save();

        $page = new \stdClass();

        $page->hasCreate = Guard::hasCreate();
        $page->createLink = $currentUrl.'/create';

        $page->filter = $this->table->getFilter();
        $page->searchQuery = $request->query('search');
        $page->searchLink = $currentUrl.'/search?'.\http_build_query($request->except('search'));

        $page->bulkAction = $this->table->getBulkAction();
        $page->tableContent = $this->table->getContent();
        $page->pagination = $this->table->getPagination();

        $page->style = Doc::getCssLinks().Doc::getCss();
        $page->script = Doc::getJsLinks().Doc::getJs();

        return $page;
    }

    public function bulkAction(Closure $callback = null)
    {
        return $this->table->bulkAction->perform($callback);
    }

    public function list($callback = null)
    {
        return $this->table->create($callback);
    }

    public function create()
    {
        $page = new \stdClass();

        $page->form = $this->form->create();

        $page->style = Doc::getCssLinks().Doc::getCss();
        $page->script = Doc::getJsLinks().Doc::getJs();

        return $page;
    }

    public function edit($id = null)
    {
        $page = new \stdClass();

        $this->form->edit($id);
        $page->form = $this->form->create();

        $page->style = Doc::getCssLinks().Doc::getCss();
        $page->script = Doc::getJsLinks().Doc::getJs();

        return $page;
    }

    public function search()
    {
        return $this->table->search();
    }

    //region Getter

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

    public function getResource()
    {
        return $this->resource;
    }

    public function isSoftDelete(): bool
    {
        return $this->isSoftDelete;
    }

    public function isDefaultFormat(): bool
    {
        return $this->format == 'default';
    }

    public function getGroupName()
    {
        return $this->groupName;
    }

    public function getName()
    {
        return $this->name;
    }

    //endregion
}
