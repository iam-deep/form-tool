<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Core\InputTypes\Common\CrudState;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class Crud
{
    public string $name;

    private $resource;
    protected DataModel $model;
    private BluePrint $bluePrint;
    private Form $form;
    private Table $table;

    protected string $format = 'default';
    protected string $groupName = 'default';
    protected bool $isSoftDelete = true;
    protected bool $isWantsJson = false;
    protected bool $isWantsArray = false;

    private $deleteRestrictForOthers = [];
    private $deleteRestrictForMe = [];
    private $deleteRestrictIsIgnore = false;
    private $deleteRestrictIgnoreColumns = [];

    private CrudState $currentState = CrudState::NONE;

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

        $this->form = new Form($this->resource, $this->bluePrint, $this->model);
        $this->form->setCrud($this);

        $this->table = new Table($this->resource, $this->bluePrint, $this->model);
        $this->table->setCrud($this);

        $this->softDelete(\config('form-tool.isSoftDelete', true));

        $this->tryGetCurrentState();

        // Set form and call the callback after all the initialization
        $this->bluePrint->setForm($this->form);
        $callback($this->bluePrint, $this->currentState);

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
     * @return \Deep\FormTool\Core\Crud
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

    public function run($except = null)
    {
        $response = $this->form->init(Arr::wrap($except));

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return $response->send();
        }

        return $this;
    }

    public function db(
        string $tableName,
        ?string $primaryId = '',
        ?string $token = '',
        ?string $orderBy = '',
        ?string $foreignKey = ''
    ): Crud {
        $this->model->db($tableName, $primaryId, $token, $orderBy, $foreignKey);

        return $this;
    }

    public function softDelete(bool $enable = true): Crud
    {
        $this->isSoftDelete = $enable;
        $this->model->softDelete($enable);

        return $this;
    }

    public function wantsJson()
    {
        $this->isWantsJson = true;

        return $this;
    }

    public function wantsArray()
    {
        $this->isWantsArray = true;

        return $this;
    }

    public function __call($method, $parameters)
    {
        return $this->form->{$method}(...$parameters);
    }

    public function saveCrud()
    {
        $foreignKey = [];
        if ($this->format == 'keyValue') {
            $foreignKey = $this->bluePrint->getSelectDbOptionsForKeyValue();
        } else {
            if (! \config('form-tool.isPreventForeignKeyDelete', true) || ($this->deleteRestrictIsIgnore && ! $this->deleteRestrictIgnoreColumns)) {
                return;
            }

            $commons = \config('form-tool.commonDeleteRestricted', []);
            foreach ($commons as $key => &$common) {
                $common = (object) $common;

                if (in_array($common->column, $this->deleteRestrictIgnoreColumns)) {
                    unset($commons[$key]);
                }
            }

            $foreignKey = $commons;
            $foreignKey = array_merge($foreignKey, $this->bluePrint->getSelectDbOptions($this->deleteRestrictIgnoreColumns));
            $foreignKey = array_merge($foreignKey, $this->deleteRestrictForOthers);
        }

        $foreignModules = $this->deleteRestrictForMe;
        if (! $foreignKey && ! $foreignModules) {
            return;
        }

        $data = new \stdClass();
        $data->foreignKey = $foreignKey;
        $data->foreignModules = $foreignModules;

        $model = $this->model;
        $data->main = (object) [
            'title' => $this->resource->title,
            'table' => $model->getTableName(),
            'id' => $model->isToken() ? $model->getTokenCol() : $model->getPrimaryId(),
        ];

        $crudData = [
            'route' => $this->resource->route,
            'data' => \json_encode($data),
            'classPath' => $this->resource::class,
        ];

        $count = DB::table('cruds')->where('route', $this->resource->route)->count();
        if ($count > 0) {
            DB::table('cruds')->where('route', $this->resource->route)->update($crudData);
        } else {
            DB::table('cruds')->insert($crudData);
        }
    }

    //region TableOptions

    public function searchIn($fields): Crud
    {
        $this->table->searchIn($fields);

        return $this;
    }

    public function deleteRestrictForOthers(string $foreignTable, string $column, ?string $label = null): Crud
    {
        // TODO: Validate if table and column is exists or need to create user test script
        $this->deleteRestrictForOthers[] = (object) ['table' => $foreignTable, 'column' => $column, 'label' => $label];

        return $this;
    }

    public function deleteRestrictForMe(
        string $foreignTable,
        string $foreignKeyColumn,
        string $foreignPrimaryCol,
        ?string $moduleName,
        ?string $route = null,
        ?string $foreignKeyLabel = null
    ): Crud {
        // TODO: Validate if table and column is exists or need to create user test script
        $this->deleteRestrictForMe[] = (object) [
            'table' => $foreignTable,
            'column' => $foreignKeyColumn,
            'primaryKey' => $foreignPrimaryCol,
            'module' => $moduleName,
            'route' => trim($route),
            'label' => $foreignKeyLabel,
        ];

        return $this;
    }

    public function ignoreDeleteRestrictions($ignoreColumns = []): Crud
    {
        $this->deleteRestrictIsIgnore = true;
        $this->deleteRestrictIgnoreColumns = $ignoreColumns;

        return $this;
    }

    //endregion

    public function index()
    {
        $request = request();

        $this->saveCrud();

        $page = new \stdClass();

        $page->buttons = $this->table->getCrudButtons();

        $filter = $this->table->getFilter();
        $page->filter = $filter->filter;
        $page->quickFilter = $filter->quickFilter;

        $page->searchQuery = $request->query('search');
        $page->searchLink = createUrl($this->resource->route.'/search', $request->except(['search', 'page']));

        $page->bulkAction = $this->table->getBulkAction();
        $page->tableContent = $this->table->getContent();
        $page->raw = $this->table->getRaw();
        $page->data = $this->table->getData();
        $page->pagination = $this->table->getPagination();

        $page->style = Doc::getCssLinks().Doc::getCss();
        $page->script = Doc::getJsLinks().Doc::getJs();

        return $page;
    }

    public function bulkAction(?Closure $callback = null)
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
        $page->id = null;

        $page->style = Doc::getCssLinks().Doc::getCss();
        $page->script = Doc::getJsLinks().Doc::getJs();

        if (request()->query('quickAdd')) {
            return response()->json([
                'status' => 1,
                'data' => [
                    'routeUpdate' => route($this->resource->route.'.store'),
                    'form' => view('form-tool::form.quick_add', ['page' => $page])->render(),
                ],
            ]);
        }

        return $page;
    }

    public function edit($id = null)
    {
        $page = new \stdClass();

        $this->form->edit($id);
        $page->form = $this->form->create();
        $page->id = $id;

        $page->style = Doc::getCssLinks().Doc::getCss();
        $page->script = Doc::getJsLinks().Doc::getJs();

        return $page;
    }

    public function search()
    {
        return $this->table->search();
    }

    public function setImportSample($data)
    {
        foreach ($data as $key => $value) {
            $input = $this->bluePrint->getInputTypeByDbField($key);
            if (! $input) {
                throw new \Exception(sprintf('Column "%s" not found in blue print!', $key));
            }

            if ($input instanceof BluePrint) {
                throw new \Exception(sprintf('Multiple table field are not supported! Field: '.$input->label));
            }

            $input->importSample($value);
        }

        return $this;
    }

    //TODO: This method need to move somewhere
    public function getOptionsByParentId()
    {
        $request = \request();
        $values = $request->post('values');
        $field = trim($request->post('field'));
        if (! $values || ! $field) {
            $data['message'] = 'Parameter "field" or "values" is missing!';

            return \response()->json($data, 400);
        }

        $bluePrint = $this->bluePrint;

        $multipleKey = trim($request->post('multipleKey'));
        if ($multipleKey) {
            $bluePrint = $this->bluePrint->getInputTypeByDbField($multipleKey);
            if (! $bluePrint) {
                $data['message'] = 'Multiple Field "'.$multipleKey.'" not found in the BluePrint!';

                return \response()->json($data, 400);
            }
        }

        $input = $bluePrint->getInputTypeByDbField($field);
        if (! $input) {
            $data['message'] = 'Field "'.$field.'" not found in the BluePrint!';

            return \response()->json($data, 400);
        }

        if (! $input instanceof \Deep\FormTool\Core\InputTypes\SelectType) {
            $data['message'] = 'Field "'.$field.'" is not a Select Type!';

            return \response()->json($data, 400);
        }

        $result = $input->getChildOptions($values);

        $data['data'] = $result;

        return \response()->json($data);
    }

    public function getCurrentState()
    {
        return $this->currentState;
    }

    private function tryGetCurrentState(): void
    {
        $manualState = Doc::getState();
        if ($manualState != CrudState::NONE) {
            $this->currentState = $manualState;

            return;
        }

        $action = null;
        $currentRoute = Route::currentRouteName();
        if ($currentRoute) {
            $segments = \explode('.', $currentRoute);
            $count = count($segments);
            if ($count > 1) {
                $route = implode('/', array_slice($segments, 0, $count - 1));
                $action = end($segments);
            } else {
                $route = $segments[0] ?? null;
            }
        }

        $state = CrudState::tryFrom($action);
        if ($state !== null) {
            $this->currentState = $state;
        }
    }

    //region Getter

    public function getForm(): Form
    {
        return $this->form;
    }

    public function getTable(): Table
    {
        return $this->table;
    }

    public function getModel(): DataModel
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

    public function getBluePrint(): BluePrint
    {
        return $this->bluePrint;
    }

    public function getField(string $column)
    {
        $input = $this->bluePrint->getInputTypeByDbField($column);
        if (! $input) {
            throw new \InvalidArgumentException(\sprintf('Field "%s" not found in the BluePrint!', $column));
        }

        return $input;
    }

    public function isWantsJson()
    {
        return $this->isWantsJson;
    }

    public function isWantsArray()
    {
        return $this->isWantsArray;
    }

    //endregion
}
