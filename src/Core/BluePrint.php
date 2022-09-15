<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Arr;

class BluePrint
{
    private $dataTypeList = [];
    public $actions = [];

    public $form = null;

    public bool $isMultiple = false;
    public $label = '';
    private $key = '';
    private $subBluePrint = [];
    private $parentBluePrint = null;

    private $multipleRequired = 0;
    private $isMultipleConfirmBeforeDelete = false;
    private $isMultipleSortable = false;
    private $multipleSortableField = '';

    private $multipleModel = null;
    private $multipleTable = null;

    public function __construct($key = '', $isMultiple = false, $parentBluePrint = null)
    {
        $this->key = $key;
        $this->isMultiple = $isMultiple;
        $this->parentBluePrint = $parentBluePrint;
    }

    public function getList(): array
    {
        return $this->_dataTypeList;
    }

    public function text(string $dbField, string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function password(string $dbField, string $label = null): InputTypes\PasswordType
    {
        $inputType = new InputTypes\PasswordType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function hidden(string $dbField, string $label = null): InputTypes\HiddenType
    {
        $inputType = new InputTypes\HiddenType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function file(string $dbField, string $label = null): InputTypes\FileType
    {
        $inputType = new InputTypes\FileType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function image(string $dbField, string $label = null): InputTypes\ImageType
    {
        $inputType = new InputTypes\ImageType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function textarea(string $dbField, string $label = null): InputTypes\TextareaType
    {
        $inputType = new InputTypes\TextareaType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function date(string $dbField, string $label = null): InputTypes\DateType
    {
        $inputType = new InputTypes\DateType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function time(string $dbField, string $label = null): InputTypes\TimeType
    {
        $inputType = new InputTypes\TimeType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function datetime(string $dbField, string $label = null): InputTypes\DateTimeType
    {
        $inputType = new InputTypes\DateTimeType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function select(string $dbField, string $label = null): InputTypes\SelectType
    {
        $inputType = new InputTypes\SelectType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function editor(string $dbField, string $label = null): InputTypes\EditorType
    {
        $inputType = new InputTypes\EditorType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function checkbox(string $dbField, string $label = null): InputTypes\CheckboxType
    {
        $inputType = new InputTypes\CheckboxType();
        $inputType->init($this, $dbField, $label);
        $this->_dataTypeList[] = $inputType;

        return $inputType;
    }

    public function custom($class, string $dbField, string $label = null)
    {
        $inputType = new $class();

        if (! $inputType instanceof InputTypes\BaseInputType) {
            throw new \Exception($class.' should extends Biswadeep\FormTool\Core\InputTypes\BaseInputType');
        }

        if (! $inputType instanceof InputTypes\ICustomType) {
            throw new \Exception($class.' should implements Biswadeep\FormTool\Core\InputTypes\ICustomType');
        }

        $this->_dataTypeList[] = $inputType;
        $inputType->init($this, $dbField, $label);

        return $inputType;
    }

    public function modify(string $dbField)
    {
        $field = $this->getInputTypeByDbField($dbField);
        if (! $field) {
            throw new \Exception('Modify field not found. Field should be in the "create" method: '.$dbField);
        }

        return $field;
    }

    public function remove($dbFields)
    {
        $fields = Arr::wrap($dbFields);

        foreach ($fields as $field) {
            foreach ($this->_dataTypeList as $key => $type) {
                if ($type->getDbField() == $field) {
                    unset($this->_dataTypeList[$key]);
                    break;
                }
            }
        }
    }

    public function getInputTypeByDbField(string $dbField)
    {
        foreach ($this->_dataTypeList as $input) {
            if (! $input instanceof BluePrint && $input->getDbField() == $dbField) {
                return $input;
            }
        }

        return null;
    }

    /*public function actions($actions) : DataType
    {
        if (!is_array($actions))
            throw new \Exception("Actions columns should be in an array! Like: ['edit', 'delete']");

        $dataType = new DataType('action', '', 'Actions');
        $this->cellList[] = $dataType->width('85px');

        foreach ($actions as $action)
            $this->actions[] = new TableAction($action);

        return $dataType;
    }*/

    //endregion

    //region Multiple

    public function multiple(string $dbField, string $label, Closure $field)
    {
        $dbField = \trim($dbField);

        $subBluePrint[$dbField] = new BluePrint($dbField, true, $this);
        $subBluePrint[$dbField]->label = $label ?? $label ?: \ucfirst($dbField);

        $field($subBluePrint[$dbField]);

        $this->_dataTypeList[] = $subBluePrint[$dbField];

        return $subBluePrint[$dbField];
    }

    public function required($noOfItems = 1)
    {
        $this->multipleRequired = \trim($noOfItems);

        return $this;
    }

    public function sortable($dbField = null)
    {
        $this->isMultipleSortable = true;

        $dbField = \trim($dbField);
        if ($dbField) {
            $this->multipleSortableField = $dbField;
            $this->hidden($dbField)->default(0)->addClass('sort-value');
        }

        return $this;
    }

    public function confirmBeforeDelete()
    {
        $this->isMultipleConfirmBeforeDelete = true;

        return $this;
    }

    public function table($model, $idCol = null, $foreignKeyCol = null, $orderBy = null)
    {
        if ($idCol && $foreignKeyCol) {
            $this->multipleTable = (object) [
                'table'         => \trim($model),
                'id'            => \trim($idCol),
                'foreignKey'    => \trim($foreignKeyCol),
                'orderBy'       => \trim($orderBy),
            ];
        } else {
            if (class_exists($model)) {
                throw new \Exception('Class not found. Class: '.$model);
            }

            $this->multipleModel = $model;

            if ($model && ! isset($model::$foreignKey)) {
                throw new \Exception('$foreignKey property not defined at '.$model);
            }
        }

        return $this;
    }

    public function keepId()
    {
        if (! $this->multipleModel && ! $this->multipleTable) {
            throw new \Exception('keepId only works with db table, Please assign the table first. And keepId must called at last.');
        }

        if ($this->isMultipleSortable && ! $this->multipleSortableField) {
            throw new \Exception('You must pass a dbField in sortable to make work with keepId. And keepId must called at last.');
        }

        if ($this->multipleModel) {
            $this->hidden($this->multipleModel::$primaryId);
        } elseif ($this->multipleTable) {
            $this->hidden($this->multipleTable->id);
        }

        return $this;
    }

    public function getRequired()
    {
        return $this->multipleRequired;
    }

    public function isSortable()
    {
        return $this->isMultipleSortable;
    }

    public function isConfirmBeforeDelete()
    {
        return $this->isMultipleConfirmBeforeDelete;
    }

    public function getModel()
    {
        if ($this->multipleTable) {
            return $this->multipleTable;
        }

        return $this->multipleModel;
    }

    public function getSortableField()
    {
        return $this->multipleSortableField;
    }

    //endregion

    public function getKey()
    {
        return $this->key;
    }

    public function getFullKey($key = '')
    {
        if ($this->parentBluePrint) {
            $key .= $this->parentBluePrint->getKey($key);

            // Preventing array brackets for the first $key
            if (! $this->parentBluePrint->isMultiple) {
                $key .= $this->key;

                return $key;
            }
        }

        if ($this->key) {
            $key .= '['.$this->key;
        } else {
            return '';
        }

        return $key.']';
    }

    public function toObj($type)
    {
        $data['fields'] = [];
        foreach ($this->_dataTypeList as $fieldType) {
            $data['fields'][] = $fieldType->toObj($type);
        }

        return $data['fields'];
    }
}
