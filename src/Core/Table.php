<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Closure;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

class Table
{
    private $bluePrint;
    private $resource;
    private $model;

    private $table;
    private $field;
    private $searchFields = [];

    private $dataResult;

    private $request;
    private $url;

    private $isFromTrash = false;

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

    public function create(?Closure $callback)
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

    public function searchIn($fields)
    {
        $this->searchFields = Arr::wrap($fields);

        return $this;
    }

    public function filter($fields = null)
    {
        $fields = Arr::wrap($fields);

        $this->filter = new Filter($fields);
        $this->filter->setBluePrint($this->bluePrint);

        return $this;
    }

    public function search()
    {
        $where = $this->makeFilter();

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
                if (! $input instanceof BluePrint &&
                    ($input->getType() == InputType::Text
                    || $input->getType() == InputType::Textarea
                    || $input->getType() == InputType::Hidden
                    || $input->getType() == InputType::Editor)
                    ) {
                    $fieldsToSearch[] = $input->getDbField();
                }
            }

            if ($this->model->isToken()) {
                $fieldsToSearch[] = $this->model->getToken();
            }
        }

        if (! $fieldsToSearch) {
            $json = new \stdClass();
            $json->isSuccess = false;
            $json->error = 'Fields not found to search!';

            return $json;
        }

        $searchTerm = $this->request->query->get('search');
        $this->dataResult = $this->model->search($searchTerm, $fieldsToSearch, $where, $this->isFromTrash);
    }

    public function listAll()
    {
        $where = $this->makeFilter();

        if ($this->request->query('search')) {
            $this->doSearch($where);
        } else {
            $this->dataResult = $this->model->getAll($where, $this->isFromTrash);
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

        if ($this->isFromTrash) {
            $tableField->datetime($metaColumns['deletedAt'] ?? 'deletedAt', 'Deleted At');
        } else {
            $tableField->actions(['edit', 'delete']);
        }

        $this->field = $tableField;
    }

    protected function createList(): object
    {
        $primaryId = $this->model->isToken() ? $this->model->getToken() : $this->model->getPrimaryId();

        if (! $this->field) {
            $this->setDefaultField();
        } elseif ($this->isFromTrash) {
            // Remove actions if we are listing trash data
            $this->field->removeActions();

            $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
            $this->field->datetime($metaColumns['deletedAt'] ?? 'deletedAt', 'Deleted At');
        }

        $data['headings'] = $data['tableData'] = [];

        foreach ($this->field->cellList as $row) {
            $row->setup();
            $data['headings'][] = $row;
        }

        $perPage = $this->dataResult->perPage();
        $page = $this->request->query('page');

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

                    foreach ($this->field->actions as $action) {
                        if ('edit' == $action->action) {
                            $viewData->data .= '<a href="'.$this->url.'/'.$value->{$primaryId}.'/edit?'.$this->request->getQueryString().'" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-pencil"></i></a>';
                        } elseif ('delete' == $action->action) {
                            $viewData->data .= ' <form action="'.$this->url.'/'.$value->{$primaryId}.'?'.$this->request->getQueryString().'" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete?\')">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                                <button class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash"></i></button>
                            </form>';
                        }
                    }
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

        if (! Guard::hasDestroy() || ! $this->crud->getSoftDelete()) {
            unset($quickFilters['trash']);
        }

        $isAllActive = true;
        $countQuickFilters = \count($quickFilters);
        $i = 0;
        foreach ($quickFilters as $key => &$row) {
            if ($key == 'all') {
                $row['count'] = $this->model->countWhere(function ($query, $class) use ($metaColumns) {
                    if ($this->crud->getSoftDelete()) {
                        $query->whereNull($metaColumns['deletedAt'] ?? 'deletedAt');
                    }
                });
            } elseif ($key == 'trash') {
                $row['count'] = $this->model->countWhere(function ($query, $class) use ($metaColumns) {
                    $query->whereNotNull($metaColumns['deletedAt'] ?? 'deletedAt');
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

    protected function makeFilter()
    {
        if ($this->request->query('quick_status') == 'trash' && Guard::hasDestroy()) {
            $this->isFromTrash = true;
        }

        if ($this->filter) {
            return $this->filter->apply();
        }

        return null;
    }

    public function getFilter()
    {
        return $this->createFilter();
    }

    //region BulkAction

    protected function createBulkAction()
    {
        $this->makeFilter();

        $bulkGroup = $this->isFromTrash ? 'trash' : 'normal';
        $data['bulkActions'] = $this->bulkAction->getActions($bulkGroup);
        $data['formAction'] = $this->url.'?'.$this->request->getQueryString();

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

    public function getModel()
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
