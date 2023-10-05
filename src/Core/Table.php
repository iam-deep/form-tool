<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISearchable;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\URL;

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
    private ?string $orderBy = null;

    private $tableMetaColumns = [
        'updatedBy' => 'updatedBy',
        'updatedAt' => 'updatedAt',
        'createdBy' => 'createdBy',
        'createdAt' => 'createdAt',
        'deletedBy' => 'deletedBy',
        'deletedAt' => 'deletedAt',
    ];

    public ?Crud $crud = null;
    public $bulkAction = null;
    public $filter = null;

    private $isButtonInit = false;
    private $crudButtons = [];
    private $primaryButtonName = null;
    private $moreButtonName = '';
    private bool $showMoreButtonAlways = false;

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

    public function setCrud(Crud $crud)
    {
        $this->crud = $crud;
    }

    public function create(?Closure $callback): Table
    {
        if ($callback) {
            $tableField = new TableField($this);

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

    /**
     * Create buttons for crud.
     *
     * @param array[string|\Deep\FormTool\Core\Button] $buttons
     * @param  string  $primaryButtonName  "name" of the primary dropdown button (Default is: _first_button)
     * @return CellDefinition
     *
     * @throws \InvalidArgumentException
     **/
    public function buttons($buttons = ['create'], ?string $primaryButtonName = '_first_button'): Table
    {
        $this->isButtonInit = true;

        $buttons = Arr::wrap($buttons);
        $this->primaryButtonName = $primaryButtonName;

        $this->crudButtons = [];
        foreach ($buttons as $button) {
            if ($button == 'create') {
                $this->crudButtons[] = Button::make('Add New', '/create', 'create')->icon('<i class="fa fa-plus"></i>');
            } elseif ($button == 'divider') {
                $this->crudButtons[] = Button::makeDivider();
            } elseif ($button instanceof Button) {
                $this->crudButtons[] = $button;
            } else {
                throw new \InvalidArgumentException(\sprintf(
                    'Button can be "create", "divider" or an instance of "%s"',
                    Button::class
                ));
            }
        }

        return $this;
    }

    public function moreButton($name, $showMoreButtonAlways = false): Table
    {
        $this->moreButtonName = $name;
        $this->showMoreButtonAlways = $showMoreButtonAlways;

        return $this;
    }

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

        $this->filter->initialize();

        return $this;
    }

    public function orderBy(string $column, string $direction = 'desc'): Table
    {
        $this->model->orderBy($column, $direction);

        return $this;
    }

    //endregion

    public function getCrudButtons()
    {
        if (! $this->isButtonInit) {
            $this->buttons();
        }

        $crudName = $this->crud->getName();
        $queryString = $this->request->getQueryString();

        $primary = null;
        $secondaries = [];
        foreach ($this->crudButtons as $button) {
            if (! $button->isActive()) {
                continue;
            }

            $search = ['{crud_name}', '{crud_url}', '{query_string}'];
            $replace = [$crudName, $this->url, $queryString];
            $button->process($search, $replace);

            if (! $primary && ! $button->isDivider()) {
                if ($this->primaryButtonName == '_first_button' || $this->primaryButtonName == $button->getName()) {
                    $primary = $button;
                    continue;
                }
            }

            $secondaries[] = $button;
        }

        return (object) ['primary' => $primary, 'secondaries' => $secondaries, 'more' => (object) [
            'name' => $this->moreButtonName,
            'isActive' => $this->moreButtonName && ($this->showMoreButtonAlways || ! $primary),
        ]];
    }

    public function search()
    {
        $where = $this->setupTable();
        $this->doSearch($where);

        $table = $this->createList();
        $table->content = $table->content->render();
        $table->pagination = $table->pagination->render();
        $table->total = \count($this->dataResult);
        $table->status = true;

        return $table;
    }

    private function doSearch($where)
    {
        $fieldsToSearch = $this->searchFields;
        if (! $fieldsToSearch) {
            foreach ($this->bluePrint->getInputList() as $input) {
                if ($input instanceof ISearchable) {
                    $fieldsToSearch[] = $input->getAlias().'.'.$input->getDbField();
                }
            }

            if ($this->model->isToken()) {
                $fieldsToSearch[] = $this->model->getTokenCol();
            }
        }

        if (! $fieldsToSearch) {
            $json = new \stdClass();
            $json->status = false;
            $json->error = 'Fields not found to search!';

            return $json;
        }

        $searchTerm = $this->request->query->get('search');
        $this->dataResult = $this->model->search(
            $searchTerm,
            $fieldsToSearch,
            $where,
            $this->orderBy,
            $this->request->query('direction') == 'asc' ? 'asc' : 'desc'
        );
    }

    public function listAll()
    {
        $where = $this->setupTable();
        if ($this->request->query('search')) {
            $this->doSearch($where);
        } else {
            $this->dataResult = $this->model->getAll(
                $where,
                $this->orderBy,
                $this->request->query('direction') == 'asc' ? 'asc' : 'desc'
            );
        }

        return $this->createList();
    }

    protected function setDefaultField()
    {
        $tableField = new TableField($this);

        $tableField->bulkActionCheckbox();
        $tableField->slNo();
        foreach ($this->bluePrint->getInputList() as $input) {
            if (! $input instanceof BluePrint) {
                $tableField->cellList[] = $input->getTableCell($tableField);
            }
        }

        $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
        $tableField->datetime($metaColumns['createdAt'] ?? 'createdAt', 'Created At');

        if (! $this->isFromTrash) {
            $tableField->actions(['edit', 'delete']);
        }

        $this->field = $tableField;
    }

    public function getFields(): TableField
    {
        if (! $this->field) {
            $this->setDefaultField();
        }

        return $this->field;
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

        $orderUrlQueryString = \http_build_query($this->request->except(['orderby', 'direction', 'page']));

        $data['headings'] = $data['tableData'] = [];
        $data['route'] = $this->url;

        // Let's remove the alias from orderBy column
        $orderBy = $this->orderBy;
        $pos = \strpos($this->orderBy, '.');
        if ($pos !== false) {
            $orderBy = \substr($this->orderBy, $pos + 1);
        }

        foreach ($this->field->cellList as $row) {
            $row->setup();

            if ($row->isOrderable()) {
                $orderColumn = $row->getOrderByColumn();
                $aliasPos = \strpos($orderColumn, '.');
                if ($aliasPos !== false) {
                    $orderColumn = \substr($orderColumn, $aliasPos + 1);
                }

                if ($orderBy == $orderColumn) {
                    $row->isOrdered = true;
                    $row->direction = $this->request->query('direction', 'desc');

                    $row->orderUrl = '?orderby='.$orderColumn.'&direction='.($row->direction == 'asc' ? 'desc' : 'asc').
                        '&'.$orderUrlQueryString;
                } else {
                    $row->orderUrl = '?orderby='.$orderColumn.'&direction=desc&'.$orderUrlQueryString;
                }
            }

            $data['headings'][] = $row;
        }

        $crudName = $this->crud->getName();
        $queryString = $this->request->getQueryString();

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
                    $viewData->data = '<input type="checkbox" class="bulk" name="bulk[]" value="'.
                        ($value->{$primaryId} ?? '').'">';
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
                                $inputType = $this->bluePrint->getInputTypeByDbField($con);
                                if ($inputType) {
                                    $inputType->setValue($value->{$con});
                                    $values[] = $inputType->getNiceValue($inputType->getValue());
                                } else {
                                    $values[] = $value->{$con};
                                }
                            } else {
                                $values[] = '<b class="text-danger">DB FIELD "'.$con.'" NOT FOUND</b>';
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
                                    $values[] = '<b class="text-danger">DB FIELD "'.$con.'" NOT FOUND</b>';
                                }
                            }
                        }
                        $viewData->data = \vsprintf('%s'.$concat->pattern, $values);
                    }
                } elseif ($cell->fieldType == 'action') {
                    if (! isset($value->{$primaryId})) {
                        $viewData->data = '<b class="text-danger">PRIMARY ID is NULL</b>';
                        $viewRow->columns[] = $viewData;
                        continue;
                    }

                    $search = ['{id}', '{crud_name}', '{crud_url}', '{query_string}'];
                    $replace = [$value->{$primaryId}, $crudName, $this->url, $queryString];
                    $buttons = $this->field->getActionButtons();
                    if ($buttons->primary) {
                        $buttons->primary = clone $buttons->primary;
                        $buttons->primary->process($search, $replace);
                    }

                    foreach ($buttons->secondaries as $key => $button) {
                        $buttons->secondaries[$key] = clone $button;
                        $buttons->secondaries[$key]->process($search, $replace);
                    }

                    $buttonData['buttons'] = $buttons;
                    $viewData->data = \view('form-tool::list.actions', $buttonData);
                } else {
                    $viewData->data = '<b class="text-danger">DB FIELD NOT FOUND</b>';
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
        $this->table->raw = $data;
        $this->table->data = $this->dataResult;
        $this->table->content = view('form-tool::list.table', $data);
        $this->table->pagination = $this->dataResult->onEachSide(2)->withQueryString()->links();

        return $this->table;
    }

    protected function createFilter()
    {
        if ($this->filter) {
            $data['filterData'] = $this->filter->create();
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
                        $query->whereNull($this->model->getAlias().'.'.$deletedAt);
                    }
                });
            } elseif ($key == 'trash') {
                $row['count'] = $this->model->countWhere(function ($query, $class) use ($deletedAt) {
                    $query->whereNotNull($this->model->getAlias().'.'.$deletedAt);
                });
            }

            if ($this->request->query('quick_status') == $key) {
                $row['active'] = true;
                $isAllActive = false;

                if (isset($data['filterData'])) {
                    $data['filterData']->inputs[] = '<input type="hidden" name="quick_status" value="'.$key.'">';
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

        return (object) [
            'filter' => \view('form-tool::list.filter', $data),
            'quickFilter' => \view('form-tool::list.quick_filter', $data),
        ];
    }

    protected function setupTable()
    {
        // Set default fields if not exists
        $this->getFields();

        $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        // Let's setup filter first
        $where = [];
        if ($this->request->query('quick_status') == 'trash' && Guard::hasDestroy()) {
            $this->isFromTrash = true;

            $where[] = function ($query) use ($deletedAt) {
                $query->whereNotNull($this->model->getAlias().'.'.$deletedAt);
            };
        } else {
            $where[] = function ($query) use ($deletedAt) {
                $query->whereNull($this->model->getAlias().'.'.$deletedAt);
            };
        }

        if ($this->request->query('id')) {
            $primaryId = $this->model->isToken() ? $this->model->getTokenCol() : $this->model->getPrimaryId();
            $where[] = [$this->model->getAlias().'.'.$primaryId => $this->request->query('id')];

            return $where;
        }

        if ($this->filter) {
            $isFilter = $this->filter->apply();
            if ($isFilter) {
                $where = array_merge($where, [$isFilter]);
            }
        }

        // Let's setup order
        $this->orderBy = null;
        $requestOrderBy = $this->request->query('orderby');
        if ($requestOrderBy) {
            foreach ($this->field->cellList as $field) {
                $orderColumn = $field->getOrderByColumn();
                $aliasPos = \strpos($orderColumn, '.');
                if ($aliasPos !== false) {
                    $orderColumn = \substr($orderColumn, $aliasPos + 1);
                }
                if ($field->isOrderable() && $orderColumn == $requestOrderBy) {
                    if ($aliasPos !== false) {
                        $this->orderBy = $field->getOrderByColumn();
                    } else {
                        $inputType = $field->getInputType();
                        if ($inputType) {
                            $this->orderBy = $inputType->getAlias().'.'.$field->getOrderByColumn();
                        } else {
                            $this->orderBy = $field->getOrderByColumn();
                        }
                    }
                    break;
                }
            }
        } elseif ($this->isFromTrash && ! $this->orderBy) {
            $this->orderBy = $this->model->getAlias().'.'.$deletedAt;
        } else {
            $this->orderBy = $this->model->getAlias().'.'.$this->model->getOrderBy();
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

    public function getRaw()
    {
        return $this->table->raw;
    }

    public function getData()
    {
        return $this->table->data;
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
