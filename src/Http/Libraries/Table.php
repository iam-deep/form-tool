<?php

namespace Biswadeep\FormTool\Http\Libraries;

class Table
{
    private $_dataModel;
    private $_resource;
    private $_model;

    public $_table;
    public $field;

    private $_url;

    public function __construct($resource, $model, DataModel $dataModel = null)
    {
        $this->_resource = $resource;
        $this->_model = $model;

        if ($dataModel)
            $this->_dataModel = $dataModel;
        else
            $this->_dataModel = DataModel::getInstance();

        $this->_url = config('form-tool.adminURL') . '/' . $resource->route;
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

    public function setTableField(TableField $tableField) : Table
    {
        $this->field = $tableField;
        return $this;
    }

    private function setDefaultField()
    {
        $tableField = new TableField($this);

        $tableField->slNo();
        foreach ($this->_dataModel->getList() as $input) {
            if (! $input instanceof DataModel) {
                $tableField->cellList[] = $input->getTableCell();
            }
        }

        $tableField->datetime('createdAt', 'Created At');

        $tableField->actions(['edit', 'delete']);

        $this->field = $tableField;
    }

    private function create() : object
    {
        $result = $this->_model::getAll();

        if (!$this->field)
            $this->setDefaultField();        

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

                if ($cell->fieldType == '_slno')
                    $viewData->data = ++$i;
                else {
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
                                    }
                                    else {
                                        $values[] = '<b class="text-red">DB FIELD "'. $con .'" NOT FOUND</b>';
                                    }
                                }
                            }
                            $viewData->data = \vsprintf('%s' . $concat->pattern, $values);
                        }

                        /*switch ($cell->fieldType) {
                            case 'date':
                                $viewData->data = $val ? date('d M, Y', strtotime($val)) : '';
                                break;
                            case 'time':
                                $viewData->data = $val ? date('h:i s', strtotime($val)) : '';
                                break;
                            case 'datetime':
                                $viewData->data = $val ? date('d M, Y h:i A', strtotime($val)) : '';
                                break;
                            case 'status':
                                $viewData->data = 1 ? 'Active' : 'Inactive';
                                break;
                            default:
                                $viewData->data = $val;
                                break;
                        }*/
                    }
                    else if ($cell->fieldType == 'action') {
                        if (!isset($value->{$this->_model::$primaryId}))
                        {
                            $viewData->data = '<b class="text-red">PRIMARY ID is NULL</b>';
                            continue;
                        }

                        foreach ($this->field->actions as $action) {
                            if ('edit' == $action->action)
                                $viewData->data .= '<a href="' . $this->_url . '/'. $value->{$this->_model::$primaryId} .'/edit" class="btn btn-primary btn-flat btn-sm"><i class="fa fa-pencil"></i></a>';
                            else if ('delete' == $action->action)
                                $viewData->data .= ' <form action="'. $this->_url . '/'. $value->{$this->_model::$primaryId} .'" method="POST" style="display:inline;" onsubmit="return confirm(\'Are you sure you want to delete?\')">
                                    '. csrf_field() .'
                                    '. method_field('DELETE') .'
                                    <button class="btn btn-danger btn-flat btn-sm"><i class="fa fa-trash"></i></button>
                                </form>';
                        }
                    }
                    else
                        $viewData->data = '<b class="text-red">DB FIELD NOT FOUND</b>';
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
        if (isset($this->_table->pagination))
            return $this->_table->pagination;

        return null;
    }

    public function getDataModel() : DataModel
    {
        return $this->_dataModel;
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