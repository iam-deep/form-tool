<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Arr;

class BluePrint
{
    private $dataTypeList = [];

    private $heroDbField = null;

    private $form = null;

    public bool $isMultiple = false;
    public $label = '';
    private $key = '';
    private $subBluePrint = [];
    private $parentBluePrint = null;

    private $multipleRequired = 0;
    private $isMultipleConfirmBeforeDelete = false;
    private $isMultipleOrderable = false;
    private $multipleOrderColumn = '';

    private $multipleModel = null;
    private $multipleTable = null;

    public function __construct($key = '', $isMultiple = false, $parentBluePrint = null)
    {
        $this->key = $key;
        $this->isMultiple = $isMultiple;
        $this->parentBluePrint = $parentBluePrint;
    }

    public function text(string $dbField, string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function number(string $dbField, string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);

        $inputType->type = InputTypes\Common\InputType::NUMBER;
        $inputType->typeInString = 'number';

        $inputType->validations(['numeric' => 'numeric']);

        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function email(string $dbField, string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);

        $inputType->type = InputTypes\Common\InputType::EMAIL;
        $inputType->typeInString = 'email';
        $inputType->inputType = 'email';

        $inputType->validations(['email' => 'email']);

        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function password(string $dbField, string $label = null): InputTypes\PasswordType
    {
        $inputType = new InputTypes\PasswordType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function hidden(string $dbField, string $label = null): InputTypes\HiddenType
    {
        $inputType = new InputTypes\HiddenType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function file(string $dbField, string $label = null): InputTypes\FileType
    {
        $inputType = new InputTypes\FileType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function image(string $dbField, string $label = null): InputTypes\ImageType
    {
        $inputType = new InputTypes\ImageType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function textarea(string $dbField, string $label = null): InputTypes\TextareaType
    {
        $inputType = new InputTypes\TextareaType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function date(string $dbField, string $label = null): InputTypes\DateType
    {
        $inputType = new InputTypes\DateType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function time(string $dbField, string $label = null): InputTypes\TimeType
    {
        $inputType = new InputTypes\TimeType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function datetime(string $dbField, string $label = null): InputTypes\DateTimeType
    {
        $inputType = new InputTypes\DateTimeType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function select(string $dbField, string $label = null): InputTypes\SelectType
    {
        $inputType = new InputTypes\SelectType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function editor(string $dbField, string $label = null): InputTypes\EditorType
    {
        $inputType = new InputTypes\EditorType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function checkbox(string $dbField, string $label = null): InputTypes\CheckboxType
    {
        $inputType = new InputTypes\CheckboxType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[] = $inputType;

        return $inputType;
    }

    public function custom($class, string $dbField, string $label = null)
    {
        $inputType = new $class();

        if (! $inputType instanceof InputTypes\BaseInputType) {
            throw new \InvalidArgumentException(\sprintf(
                '%s should extends Biswadeep\FormTool\Core\InputTypes\BaseInputType',
                $class
            ));
        }

        if (! $inputType instanceof InputTypes\Common\ICustomType) {
            throw new \InvalidArgumentException(\sprintf(
                '%s should implements Biswadeep\FormTool\Core\InputTypes\Common\ICustomType',
                $class
            ));
        }

        $this->dataTypeList[] = $inputType;
        $inputType->init($this, $dbField, $label);

        return $inputType;
    }

    public function modify(string $dbField)
    {
        $field = $this->getInputTypeByDbField($dbField);
        if (! $field) {
            throw new \InvalidArgumentException(\sprintf(
                'Modify field not found. Field should be in the "create" method: %s',
                $dbField
            ));
        }

        return $field;
    }

    public function remove($dbFields)
    {
        $fields = Arr::wrap($dbFields);

        foreach ($fields as $field) {
            foreach ($this->dataTypeList as $key => $type) {
                if ($type->getDbField() == $field) {
                    unset($this->dataTypeList[$key]);
                    break;
                }
            }
        }
    }

    public function getInputTypeByDbField(string $column)
    {
        foreach ($this->dataTypeList as $input) {
            if ($input instanceof BluePrint) {
                if ($input->getKey() == $column) {
                    return $input;
                }
            } elseif ($input->getDbField() == $column) {
                return $input;
            }
        }

        return null;
    }

    //endregion

    //region Multiple

    public function multiple(string $dbField, string $label, Closure $field)
    {
        $dbField = \trim($dbField);

        $subBluePrint[$dbField] = new BluePrint($dbField, true, $this);
        $subBluePrint[$dbField]->setForm($this->form);
        $subBluePrint[$dbField]->label = $label ?: \ucfirst($dbField);

        $field($subBluePrint[$dbField]);

        $this->dataTypeList[] = $subBluePrint[$dbField];

        return $subBluePrint[$dbField];
    }

    public function required($noOfItems = 1)
    {
        $this->multipleRequired = \trim($noOfItems);

        return $this;
    }

    public function orderable($column = null)
    {
        $this->isMultipleOrderable = true;

        $column = \trim($column);
        if ($column) {
            $this->multipleOrderColumn = $column;
            $this->hidden($column)->default(0)->addClass('order-value');
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
                'table' => \trim($model),
                'id' => \trim($idCol),
                'foreignKey' => \trim($foreignKeyCol),
                'orderBy' => \trim($orderBy),
            ];
        } else {
            if (class_exists($model)) {
                throw new \InvalidArgumentException('Class not found. Class: '.$model);
            }

            $this->multipleModel = $model;

            if ($model && ! isset($model::$foreignKey)) {
                throw new \InvalidArgumentException('$foreignKey property not defined at '.$model);
            }
        }

        return $this;
    }

    public function keepId()
    {
        if (! $this->multipleModel && ! $this->multipleTable) {
            throw new \InvalidArgumentException(
                'keepId only works with db table, Please assign the table first. And keepId must called at last.'
            );
        }

        if ($this->isMultipleOrderable && ! $this->multipleOrderColumn) {
            throw new \InvalidArgumentException(
                'You must pass a dbField in orderable to make work with keepId. And keepId must called at last.'
            );
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

    public function isOrderable()
    {
        return $this->isMultipleOrderable;
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

    public function getOrderByColumn()
    {
        return $this->multipleOrderColumn;
    }

    //endregion

    //region GetterAndSetter

    public function setForm($form)
    {
        $this->form = $form;
    }

    public function getForm()
    {
        return $this->form;
    }

    public function getList(): array
    {
        return $this->dataTypeList;
    }

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

    public function setHeroField($field)
    {
        $this->heroDbField = $field;
    }

    public function getHeroField()
    {
        if ($this->heroDbField) {
            return $this->heroDbField;
        }

        foreach ($this->dataTypeList as $input) {
            if (isset($input->type) && $input->type == InputTypes\Common\InputType::TEXT && ! $input->isEncrypted()) {
                return $input->getDbField();
            }
        }

        return null;
    }

    public function getSelectDbOptions()
    {
        $selects = [];
        foreach ($this->dataTypeList as $input) {
            if (! $input instanceof BluePrint && $input->type == InputTypes\Common\InputType::SELECT) {
                foreach ($input->getOptionData() as $options) {
                    foreach ($options as $type => $optionData) {
                        if ($type == 'db') {
                            $selects[] = (object) [
                                'table' => $optionData->table,
                                'column' => $input->getDbField(),
                                'label' => $input->getLabel(),
                            ];
                        }
                    }
                }
            }
        }

        $data = new \stdClass();

        $temp = (object) [
            'table' => 'users',
            'column' => 'createdBy',
            'label' => 'Created By',
        ];
        $selects[] = $temp;

        if ($selects) {
            $data->foreignKey = $selects;

            $model = $this->form->getModel();
            $data->main = (object) [
                'title' => $this->form->getResource()->title,
                'table' => $model->getTableName(),
                'id' => $model->isToken() ? $model->getTokenCol() : $model->getPrimaryId(),
            ];
        }

        return $data;
    }

    //endregion

    public function toObj($type)
    {
        $data['fields'] = [];
        foreach ($this->dataTypeList as $fieldType) {
            $data['fields'][] = $fieldType->toObj($type);
        }

        return $data['fields'];
    }
}
