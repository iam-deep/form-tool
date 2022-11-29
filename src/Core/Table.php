<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Core\InputTypes\Common\ISearchable;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;
use Illuminate\Pagination\Paginator;

class Table
{
    private BluePrint $bluePrint;
    private $resource;
    private DataModel $model;

    private $table;
    private $field;
    private $searchFields = [];

    private $dataResult;

    private Request $request;
    private $url;

    private $isFromTrash = false;
    private ?string $sortBy = null;

    private $tableMetaColumns = [
        'updatedBy' => 'updatedBy',
        'updatedAt' => 'updatedAt',
        'createdBy' => 'createdBy',
        'createdAt' => 'createdAt',
        'deletedBy' => 'deletedBy',
        'deletedAt' => 'deletedAt',
    ];

    public $crud = null;
    public $bulkAction = null;
    public $filter = null;

    public function __construct($resource, BluePrint $bluePrint, DataModel $model)
    {
        $this->resource = $resource;
        $this->bluePrint = $bluePrint;
        $this->model = $model;

        $this->bulkAction = new BulkAction();
        $this->bulkAction->setTable($this);

        $this->request = request();
        $this->url = URL::to(config('form-tool.adminURL').'/'.$resource->route);
    }

    public function setCrud($crud)
    {
        $this->crud = $crud;
    }

    public function create(?Closure $callback): Table
    {
        if ($callback) {
            $tableField = new TableField($this);
            $tableField->bulkActionCheckbox();

            $callback($tableField);
            $this->setTableField($tableField);
        }

        return $this;
    }

    public function setTableField(TableField $tableField): Table
    {
        $this->field = $tableField;

        return $this;
    }

    //region Options

    public function searchIn($fields): Table
    {
        $this->searchFields = Arr::wrap($fields);

        return $this;
    }

    public function filter($fields = null): Table
    {
        $fields = Arr::wrap($fields);

        $this->filter = new Filter($fields);
        $this->filter->setBluePrint($this->bluePrint);

        return $this;
    }

    public function orderBy(string $field): Table
    {
        $this->model->orderBy($field);

        return $this;
    }

    //endregion

    public function search()
    {
        $where = $this->setupTable();
        $this->doSearch($where);

        $table = $this->createList();
        $table->content = $table->content->render();
        $table->pagination = $table->pagination->render();
        $table->total = \count($this->dataResult);
        $table->isSuccess = true;

        return $table;
    }

    private function doSearch($where)
    {
        $fieldsToSearch = $this->searchFields;
        if (! $fieldsToSearch) {
            foreach ($this->bluePrint->getList() as $input) {
                if ($input instanceof ISearchable) {
                    $fieldsToSearch[] = $input->getDbField();
                }
            }

            if ($this->model->isToken()) {
                $fieldsToSearch[] = $this->model->getTokenCol();
            }
        }

        if (! $fieldsToSearch) {
            $json = new \stdClass();
            $json->isSuccess = false;
            $json->error = 'Fields not found to search!';

            return $json;
        }

        $searchTerm = $this->request->query->get('search');
        $this->dataResult = $this->model->search($searchTerm, $fieldsToSearch, $where, $this->sortBy);
    }

    public function listAll()
    {
        $where = $this->setupTable();
        if ($this->request->query('search')) {
            $this->doSearch($where);
        } else {
            $this->dataResult = $this->model->getAll($where, $this->sortBy);
        }

        return $this->createList();
    }

    protected function setDefaultField()
    {
        $tableField = new TableField($this);

        $tableField->bulkActionCheckbox();
        $tableField->slNo();
        foreach ($this->bluePrint->getList() as $input) {
            if (! $input instanceof BluePrint) {
                $tableField->cellList[] = $input->getTableCell();
            }
        }

        $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
        $tableField->datetime($metaColumns['createdAt'] ?? 'createdAt', 'Created At');

        if (! $this->isFromTrash) {
            $tableField->actions(['edit', 'delete']);
        }

        $this->field = $tableField;
    }

    protected function createList(): object
    {
        $primaryId = $this->model->isToken() ? $this->model->getTokenCol() : $this->model->getPrimaryId();

        if ($this->isFromTrash) {
            // Remove actions if we are listing trash data
            $this->field->removeActions();

            $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
            $this->field->datetime($metaColumns['deletedAt'] ?? 'deletedAt', 'Deleted At');
        }

        $sortUrlQueryString = \http_build_query($this->request->except(['sortby', 'order', 'page']));

        $data['headings'] = $data['tableData'] = [];
        $data['route'] = $this->url;

        foreach ($this->field->cellList as $row) {
            $row->setup();

            if ($row->isSortable()) {
                $sortedField = $row->getSortableField();
                if ($this->sortBy == $sortedField) {
                    $row->isSorted = true;
                    $row->sortedOrder = $this->request->query('order', 'desc');

                    $row->sortUrl = '?sortby='.$sortedField.'&order='.($row->sortedOrder == 'asc' ? 'desc' : 'asc').'&'.$sortUrlQueryString;
                } else {
                    $row->sortUrl = '?sortby='.$sortedField.'&order=desc&'.$sortUrlQueryString;
                }
            }
            
            $data['headings'][] = $row;
        }

        $crudName = $this->crud->getName();

        $perPage = $this->dataResult->perPage();
        $page = (int) $this->request->query('page');

        $i = $page ? (($page - 1) * $perPage) : 0;
        foreach ($this->dataResult as $value) {
            $viewRow = new TableViewRow();
            foreach ($this->field->cellList as $cell) {
                $viewData = new TableViewCol();
                $viewData->raw = $cell->raw;

                if ($cell->fieldType == '_slno') {
                    $viewData->data = ++$i;
                    $viewRow->columns[] = $viewData;
                    continue;
                } elseif ($cell->fieldType == '_bulk') {
                    $viewData->data = '<input type="checkbox" class="bulk" name="bulk[]" value="'.($value->{$primaryId} ?? '').'">';
                    $viewRow->columns[] = $viewData;
                    continue;
                } elseif ($cell->fieldType == '_any') {
                    $concat = $cell->getConcat();
                    if (! $concat->pattern) {
                        $viewData->data = 'Nothing specified!';
                        $viewRow->columns[] = $viewData;
                        continue;
                    }

                    if ($concat->pattern instanceof Closure) {
                        $closure = $concat->pattern;
                        $viewData->data = $closure($value);
                        $viewRow->columns[] = $viewData;
                        continue;
                    }

                    $values = [];
                    if ($concat->dbFields) {
                        foreach ($concat->dbFields as $con) {
                            $con = \trim($con);
                            if (\property_exists($value, $con)) {
                                $values[] = $value->{$con};
                            } else {
                                $values[] = '<b class="text-red">DB FIELD "'.$con.'" NOT FOUND</b>';
                            }
                        }
                    }
                    $viewData->data = \vsprintf($concat->pattern, $values);
                    $viewRow->columns[] = $viewData;
                    continue;
                }

                // We can't use isset here as isset will be false if value is null, and value can be null
                if (\property_exists($value, $cell->getDbField())) {
                    $cell->setValue($value->{$cell->getDbField()});
                    $viewData->data = $cell->getValue();

                    $concat = $cell->getConcat();
                    if ($concat) {
                        $values = [];
                        $values[] = $viewData->data;
                        if ($concat->dbFields) {
                            foreach ($concat->dbFields as $con) {
                                $con = \trim($con);
                                if (\property_exists($value, $con)) {
                                    $values[] = $value->{$con};
                                } else {
                                    $values[] = '<b class="text-red">DB FIELD "'.$con.'" NOT FOUND</b>';
                                }
                            }
                        }
                        $viewData->data = \vsprintf('%s'.$concat->pattern, $values);
                    }
                } elseif ($cell->fieldType == 'action') {
                    if (! isset($value->{$primaryId})) {
                        $viewData->data = '<b class="text-red">PRIMARY ID is NULL</b>';
                        $viewRow->columns[] = $viewData;
                        continue;
                    }

                    $actionData['primary'] = null;
                    $actionData['secondaries'] = [];
                    foreach ($this->field->actions as $action) {
                        if ('edit' == $action->action) {
                            $actionData['primary'] = new \stdClass();
                            $actionData['primary']->link = $this->url.'/'.$value->{$primaryId}.'/edit?'.$this->request->getQueryString();
                            $actionData['primary']->text = 'Edit';
                        } elseif ('delete' == $action->action) {
                            $deleteSelectorId = $crudName.'_delete_'.$value->{$primaryId};

                            $button = new \stdClass();
                            $button->type = 'html';
                            $button->html = '<a href="javascript:;" onClick="$(\'#'.$deleteSelectorId.'\').submit()"><i class="fa fa-trash"></i> Delete</a>
                            <form id="'.$deleteSelectorId.'" action="'.$this->url.'/'.$value->{$primaryId}.'?'.$this->request->getQueryString().'" method="POST" onsubmit="return confirm(\'Are you sure you want to delete?\')" style="display:none">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                            </form>';

                            $actionData['secondaries'][] = $button;
                        }
                    }

                    $viewData->data = \view('form-tool::list.actions', $actionData);
                } else {
                    $viewData->data = '<b class="text-red">DB FIELD NOT FOUND</b>';
                }

                $viewRow->columns[] = $viewData;
            }

            $data['tableData'][] = $viewRow;
        }

        if (! \count($this->dataResult)) {
            $viewData = new TableViewCol();
            $viewData->data = 'Nothing found!';
            $viewData->raw = 'style="text-align:center;font-style: italic;" colspan="'.\count($data['headings']).'"';

            $viewRow = new TableViewRow();
            $viewRow->columns[] = $viewData;

            $data['tableData'][] = $viewRow;
        }

        // Set paginator base url, needed for search result
        $this->dataResult->withPath($this->resource->route);

        $this->table = new \stdClass();
        $this->table->content = view('form-tool::list.table', $data);
        $this->table->pagination = $this->dataResult->onEachSide(2)->withQueryString()->links();

        return $this->table;
    }

    protected function createFilter()
    {
        if ($this->filter) {
            $data['filterInputs'] = $this->filter->create();
        }

        $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        $quickFilters = [
            'all' => [
                'href' => $this->url,
                'label' => 'All',
                'count' => 0,
                'active' => false,
                'separator' => true,
            ],
            'trash' => [
                'href' => $this->url.'?quick_status=trash',
                'label' => 'Trash',
                'count' => 0,
                'active' => false,
                'separator' => true,
            ],
        ];

        if (! Guard::hasDestroy() || ! $this->crud->isSoftDelete()) {
            unset($quickFilters['trash']);
        }

        $isAllActive = true;
        $countQuickFilters = \count($quickFilters);
        $i = 0;
        foreach ($quickFilters as $key => &$row) {
            if ($key == 'all') {
                $row['count'] = $this->model->countWhere(function ($query, $class) use ($deletedAt) {
                    if ($this->crud->isSoftDelete()) {
                        $query->whereNull($deletedAt);
                    }
                });
            } elseif ($key == 'trash') {
                $row['count'] = $this->model->countWhere(function ($query, $class) use ($deletedAt) {
                    $query->whereNotNull($deletedAt);
                });
            }

            if ($this->request->query('quick_status') == $key) {
                $row['active'] = true;
                $isAllActive = false;
                $url = $row['href'];

                if (isset($data['filterInputs'])) {
                    $data['filterInputs'][] = '<input type="hidden" name="quick_status" value="'.$key.'">';
                }
            }

            if (++$i == $countQuickFilters) {
                $row['separator'] = false;
            }
        }

        if ($isAllActive) {
            $quickFilters['all']['active'] = true;
        }

        $data['quickFilters'] = $quickFilters;

        return \view('form-tool::list.filter', $data);
    }

    protected function setupTable()
    {
        $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        // Let's setup filter first
        $where = [];
        if ($this->request->query('quick_status') == 'trash' && Guard::hasDestroy()) {
            $this->isFromTrash = true;
            
            $where[] = function($query) use ($deletedAt) {
                $query->whereNotNull($deletedAt);
            }; 
        } else {
            $where[] = function($query) use ($deletedAt) {
                $query->whereNull($deletedAt);
            };
        }

        if ($this->request->query('id')) {
            $primaryId = $this->model->isToken() ? $this->model->getTokenCol() : $this->model->getPrimaryId();
            $where[] = [$primaryId => $this->request->query('id')];

            return $where;
        }

        if ($this->filter) {
            $isFilter = $this->filter->apply();
            if ($isFilter) {
                $where = array_merge($where, [$isFilter]);
            }
        }

        // Let's setup sort
        if (! $this->field) {
            $this->setDefaultField();
        }

        $this->sortBy = null;
        $requestSortBy = $this->request->query('sortby');
        if ($requestSortBy) {
            foreach ($this->field->cellList as $field) {
                if ($field->isSortable() && $field->getSortableField() == $requestSortBy) {
                    $this->sortBy = $field->getSortableField();
                    break;
                }
            }
        } else if ($this->isFromTrash && ! $this->sortBy) {
            $this->sortBy = $deletedAt;
        } else {
            $this->sortBy = $this->model->getOrderBy();
        }

        return $where;
    }

    public function getFilter()
    {
        return $this->createFilter();
    }

    //region BulkAction

    protected function createBulkAction()
    {
        if ($this->request->query('quick_status') == 'trash' && Guard::hasDestroy()) {
            $this->isFromTrash = true;
        }

        $bulkGroup = $this->isFromTrash ? 'trash' : 'normal';
        $data['bulkActions'] = $this->bulkAction->getActions($bulkGroup);
        $data['formAction'] = $this->url.'/bulk-action?'.$this->request->getQueryString();

        return \view('form-tool::list.bulk_action', $data);
    }

    public function getBulkAction()
    {
        return $this->createBulkAction();
    }

    //endregion

    public function getContent()
    {
        $this->listAll();

        return $this->table->content;
    }

    public function getPagination()
    {
        if (isset($this->table->pagination)) {
            return $this->table->pagination;
        }

        return null;
    }

    public function getBluePrint(): BluePrint
    {
        return $this->bluePrint;
    }

    public function getModel(): DataModel
    {
        return $this->model;
    }

    public function getTableMetaColumns()
    {
        return $this->tableMetaColumns;
    }
}

class TableViewRow
{
    public $columns = [];
    public string $raw = '';
}

class TableViewCol
{
    public $data = null;
    public string $raw = '';
}
