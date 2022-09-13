<?php

namespace Biswadeep\FormTool\Core;

class Table
{
    private $_bluePrint;
    private $_resource;
    private $_model;

    public $_table;
    public $field;

    private $_url;

    public function __construct($resource, BluePrint $bluePrint = null, $model = null)
    {
        $this->_resource = $resource;

        if ($bluePrint) {
            $this->_bluePrint = $bluePrint;
        } else {
            $this->_bluePrint = BluePrint::getInstance();
        }

        $this->_model = $model;

        $this->_url = config('form-tool.adminURL').'/'.$resource->route;
        //$this->_url = url()->current();

        /*if (is_array($result)) {
            $result = $result;
        }
        else if (is_string($result)) {
            $this->model = $result;
            $result = $result::getAll();

            $this->pagination = $result->onEachSide(2)->links();
        }*/
    }

    public function setTableField(TableField $tableField): Table
    {
        $this->field = $tableField;

        return $this;
    }

    private function setDefaultField()
    {
        $tableField = new TableField($this);

        $tableField->slNo();
        foreach ($this->_bluePrint->getList() as $input) {
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
        $result = $this->_model->getAll();
        $primaryId = $this->_model->getPrimaryId();

        if (! $this->field) {
            $this->setDefaultField();
        }

        $data['headings'] = $data['tableData'] = [];

        foreach ($this->field->cellList as $row) {
            $row->setup();
            $data['headings'][] = $row;
        }

        $i = 0;
        foreach ($result as $value) {
            $viewRow = new TableViewRow();
            foreach ($this->field->cellList as $cell) {
                $viewData = new TableViewCol();
                $viewData->styleClass = $cell->styleClass;
                $viewData->styleCSS = $cell->styleCSS;

                if ($cell->fieldType == '_slno') {
                    $viewData->data = ++$i;
                } else {
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
                            continue;
                        }

                        foreach ($this->field->actions as $action) {
                            if ('edit' == $action->action) {
                                $viewData->data .= '<a href="'.$this->_url.'/'.$value->{$primaryId}.'/edit" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-pencil"></i></a>';
                            } elseif ('delete' == $action->action) {
                                $viewData->data .= ' <form action="'.$this->_url.'/'.$value->{$primaryId}.'" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete?\')">
                                    '.csrf_field().'
                                    '.method_field('DELETE').'
                                    <button class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash"></i></button>
                                </form>';
                            }
                        }
                    } else {
                        $viewData->data = '<b class="text-red">DB FIELD NOT FOUND</b>';
                    }
                }

                $viewRow->columns[] = $viewData;
            }

            $data['tableData'][] = $viewRow;
        }

        $this->_table = new \stdClass();
        $this->_table->content = view('form-tool::crud.components.table_list', $data);
        $this->_table->pagination = $result->onEachSide(2)->links();

        return $this->_table;
    }

    public function getContent()
    {
        $this->create();

        return $this->_table->content;
    }

    public function getPagination()
    {
        if (isset($this->_table->pagination)) {
            return $this->_table->pagination;
        }

        return null;
    }

    public function getBluePrint(): BluePrint
    {
        return $this->_bluePrint;
    }
}

class TableViewRow
{
    public $columns = [];
    public string $styleClass = '';
    public string $styleCSS = '';
}

class TableViewCol
{
    public $data = null;
    public string $styleClass = '';
    public string $styleCSS = '';
}
