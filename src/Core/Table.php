<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\InputType;
use Illuminate\Support\Arr;

class Table
{
    private $bluePrint;
    private $resource;
    private $model;

    private $table;
    private $field;
    private $searchFields = [];

    private $dataResult;

    private $url;

    public function __construct($resource, BluePrint $bluePrint, DataModel $model)
    {
        $this->resource = $resource;
        $this->bluePrint = $bluePrint;
        $this->model = $model;

        $this->url = config('form-tool.adminURL').'/'.$resource->route;
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

    public function search($searchTerm)
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
        }

        if (! $fieldsToSearch) {
            $json = new \stdClass();
            $json->isSuccess = false;
            $json->error = 'Fields not found to search!';

            return $json;
        }

        $this->dataResult = $this->model->search($searchTerm, $fieldsToSearch);

        $table = $this->create();
        $table->content = $table->content->render();
        $table->pagination = $table->pagination->render();
        $table->total = \count($this->dataResult);
        $table->isSuccess = true;

        return $table;
    }

    public function listAll()
    {
        $this->dataResult = $this->model->getAll();

        return $this->create();
    }

    private function setDefaultField()
    {
        $tableField = new TableField($this);

        $tableField->slNo();
        foreach ($this->bluePrint->getList() as $input) {
            if (! $input instanceof BluePrint) {
                $tableField->cellList[] = $input->getTableCell();
            }
        }

        $tableField->datetime('createdAt', 'Created At');

        $tableField->actions(['edit', 'delete']);

        $this->field = $tableField;
    }

    private function create(): object
    {
        $primaryId = $this->model->getPrimaryId();

        if (! $this->field) {
            $this->setDefaultField();
        }

        $data['headings'] = $data['tableData'] = [];

        foreach ($this->field->cellList as $row) {
            $row->setup();
            $data['headings'][] = $row;
        }

        $i = 0;
        foreach ($this->dataResult as $value) {
            $viewRow = new TableViewRow();
            foreach ($this->field->cellList as $cell) {
                $viewData = new TableViewCol();
                $viewData->raw = $cell->raw;

                if ($cell->fieldType == '_slno') {
                    $viewData->data = ++$i;
                    $viewRow->columns[] = $viewData;
                    continue;
                }

                // We can't use isset here as isset will be false is value is null, and value can be null
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
                            $viewData->data .= '<a href="'.$this->url.'/'.$value->{$primaryId}.'/edit" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-pencil"></i></a>';
                        } elseif ('delete' == $action->action) {
                            $viewData->data .= ' <form action="'.$this->url.'/'.$value->{$primaryId}.'" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete?\')">
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
        $this->table->content = view('form-tool::crud.components.table_list', $data);
        $this->table->pagination = $this->dataResult->onEachSide(2)->links();

        return $this->table;
    }

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
