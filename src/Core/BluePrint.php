<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Core\InputTypes\SelectType;
use Deep\FormTool\Core\InputTypes\TextType;
use Deep\FormTool\Dtos\MultipleTableDto;
use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Arr;

class BluePrint
{
    private $dataTypeList = [];
    private $groups = [];
    private int $groupIndex = 0;
    private string $groupName = '';

    private $heroDbField = null;

    private $form = null;

    public bool $isMultiple = false;
    public $label = '';
    private $key = '';
    private $subBluePrint = [];
    private $parentBluePrint = null;

    private $multipleRequired = 0;
    private $isMultipleConfirmBeforeDelete = false;
    // private $isMultipleOrderable = false;
    // private $multipleOrderColumn = '';

    private ?MultipleTableDto $multipleModel = null;
    // private $multipleTable = null;

    public function __construct($key = '', $isMultiple = false, $parentBluePrint = null)
    {
        $this->key = $key;
        $this->isMultiple = $isMultiple;
        $this->parentBluePrint = $parentBluePrint;

        $this->groupName = $this->groupIndex;
        $this->dataTypeList[$this->groupName] = [];
    }

    public function text(string $dbField, ?string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function number(string $dbField, ?string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);

        $inputType->type = InputTypes\Common\InputType::NUMBER;
        $inputType->typeInString = 'number';

        $inputType->validations(['numeric' => 'numeric']);

        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function email(string $dbField, ?string $label = null): InputTypes\TextType
    {
        $inputType = new InputTypes\TextType();
        $inputType->init($this, $dbField, $label);

        $inputType->type = InputTypes\Common\InputType::EMAIL;
        $inputType->typeInString = 'email';
        $inputType->inputType = 'email';

        $inputType->validations(['email' => 'email:dns']);

        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function password(string $dbField, ?string $label = null): InputTypes\PasswordType
    {
        $inputType = new InputTypes\PasswordType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function hidden(string $dbField, ?string $label = null): InputTypes\HiddenType
    {
        $inputType = new InputTypes\HiddenType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function file(string $dbField, ?string $label = null): InputTypes\FileType
    {
        $inputType = new InputTypes\FileType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function image(string $dbField, ?string $label = null): InputTypes\ImageType
    {
        $inputType = new InputTypes\ImageType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function textarea(string $dbField, ?string $label = null): InputTypes\TextareaType
    {
        $inputType = new InputTypes\TextareaType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function date(string $dbField, ?string $label = null): InputTypes\DateType
    {
        $inputType = new InputTypes\DateType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function time(string $dbField, ?string $label = null): InputTypes\TimeType
    {
        $inputType = new InputTypes\TimeType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function datetime(string $dbField, ?string $label = null): InputTypes\DateTimeType
    {
        $inputType = new InputTypes\DateTimeType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function select(string $dbField, ?string $label = null): InputTypes\SelectType
    {
        $inputType = new InputTypes\SelectType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function editor(string $dbField, ?string $label = null): InputTypes\EditorType
    {
        $inputType = new InputTypes\EditorType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function checkbox(string $dbField, ?string $label = null): InputTypes\CheckboxType
    {
        $inputType = new InputTypes\CheckboxType();
        $inputType->init($this, $dbField, $label);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    /**
     * @template T of \Deep\FormTool\Core\InputTypes\Common\ICustomType
     *
     * @param  class-string<T>  $class
     * @return T
     */
    public function custom($class, ?string $dbField = null, ?string $label = null)
    {
        $inputType = new $class();

        if (! $inputType instanceof InputTypes\BaseInputType) {
            throw new \InvalidArgumentException(\sprintf(
                '%s should extends Deep\FormTool\Core\InputTypes\BaseInputType',
                $class
            ));
        }

        if (! $inputType instanceof InputTypes\Common\ICustomType) {
            throw new \InvalidArgumentException(\sprintf(
                '%s should implements Deep\FormTool\Core\InputTypes\Common\ICustomType',
                $class
            ));
        }

        $this->dataTypeList[$this->groupName][] = $inputType;
        $inputType->init($this, $dbField, $label);

        return $inputType;
    }

    public function html(string $html, ?string $dbField = null): InputTypes\HtmlType
    {
        $inputType = new InputTypes\HtmlType();
        $inputType->init($this, $dbField, $html);
        $this->dataTypeList[$this->groupName][] = $inputType;

        return $inputType;
    }

    public function modify(string $dbField)
    {
        $field = $this->getInputTypeByDbField($dbField);
        if (! $field) {
            throw new \InvalidArgumentException(\sprintf(
                'Field not found in modify() function. Field should be in the setup() or create() function: %s',
                $dbField
            ));
        }

        return $field;
    }

    public function remove($dbFields)
    {
        $fields = Arr::wrap($dbFields);

        foreach ($fields as $field) {
            foreach ($this->dataTypeList as $groupName => $group) {
                foreach ($group as $key => $input) {
                    if (! $input instanceof InputTypes\HtmlType && ! $input instanceof BluePrint &&
                        $input->getDbField() == $field) {
                        unset($this->dataTypeList[$groupName][$key]);
                        break;
                    }
                }
            }
        }
    }

    public function getInputTypeByDbField(string $column)
    {
        foreach ($this->dataTypeList as $group) {
            foreach ($group as $input) {
                if ($input instanceof BluePrint) {
                    if ($input->getKey() == $column) {
                        return $input;
                    }
                } elseif ($input->getDbField() == $column) {
                    return $input;
                }
            }
        }

        return null;
    }

    //endregion

    //region Multiple

    public function multiple(string $dbField, string $label, Closure $field): BluePrint
    {
        $dbField = \trim($dbField);

        $this->subBluePrint[$dbField] = new BluePrint($dbField, true, $this);
        $this->subBluePrint[$dbField]->setForm($this->form);
        $this->subBluePrint[$dbField]->label = $label ?: \ucfirst($dbField);

        $field($this->subBluePrint[$dbField]);

        $this->dataTypeList[$this->groupName][] = $this->subBluePrint[$dbField];

        return $this->subBluePrint[$dbField];
    }

    public function group(string $name, Closure $fields)
    {
        if (is_numeric($name)) {
            throw new FormToolException('Group name cannot be numeric!');
        }

        if (in_array($name, $this->groups)) {
            throw new FormToolException('Group name already exists!');
        }

        $this->groups[] = $name;

        $this->groupName = $name;
        $fields($this);
        $this->groupName = ++$this->groupIndex;

        return $this;
    }

    public function required($noOfItems = 1)
    {
        $this->multipleRequired = \trim($noOfItems);

        return $this;
    }

    public function orderable($column = null)
    {
        if ($this->multipleModel == null) {
            $this->multipleModel = new MultipleTableDto();
        }

        $this->multipleModel->isOrderable = true;
        $column = \trim($column);
        if ($column) {
            $this->multipleModel->orderableColumn = \trim($column);
            $this->hidden($column)->default(0)->addClass('order-value');
        }

        // $this->isMultipleOrderable = true;

        // $column = \trim($column);
        // if ($column) {
        //     $this->multipleOrderColumn = $column;
        //     $this->hidden($column)->default(0)->addClass('order-value');
        // }

        return $this;
    }

    public function confirmBeforeDelete()
    {
        $this->isMultipleConfirmBeforeDelete = true;

        return $this;
    }

    public function table($tableOrClass, $idCol = null, $foreignKeyCol = null, $orderBy = null, ?Closure $where = null)
    {
        if ($idCol) {
            $this->multipleModel = new MultipleTableDto(
                modelType:'table',
                tableName: \trim($tableOrClass),
                primaryCol: \trim($idCol),
                foreignCol: \trim($foreignKeyCol) ?: $this->form->getModel()->getPrimaryId(),
                orderBy: \trim($orderBy),
                where: $where,
            );

            // $this->multipleTable = (object) [
            //     'table' => \trim($model),
            //     'id' => \trim($idCol),
            //     'foreignKey' => \trim($foreignKeyCol) ?: $this->form->getModel()->getPrimaryId(),
            //     'orderBy' => \trim($orderBy),
            //     'where' => $where,
            // ];
        } else {
            // if (! class_exists($model)) {
            //     throw new \InvalidArgumentException('Class not found. Class: '.$model);
            // }

            // if ($model && ! $model::$foreignKey) {
            //     throw new \InvalidArgumentException('$foreignKey property not defined at '.$model);
            // }

            // $this->multipleModel = (object) [
            //     'modelClass' => $model,
            //     'where' => $where,
            // ];

            $this->multipleModel = new MultipleTableDto(
                modelType:'class',
                className: $tableOrClass,
                where: $where,
            );
        }

        return $this;
    }

    public function keepId()
    {
        if (! $this->multipleModel) {
            throw new \InvalidArgumentException(
                'keepId only works with db table, Please assign the table first. And keepId must called at last.'
            );
        }

        if ($this->multipleModel->isOrderable && ! $this->multipleModel->orderableColumn) {
            throw new \InvalidArgumentException(
                'You must pass a dbField in orderable to make work with keepId. And keepId must called at last.'
            );
        }

        $this->hidden($this->multipleModel->primaryCol);

        return $this;
    }

    public function getRequired()
    {
        return $this->multipleRequired;
    }

    public function isOrderable(): ?bool
    {
        return $this->multipleModel?->isOrderable;
    }

    public function isConfirmBeforeDelete()
    {
        return $this->isMultipleConfirmBeforeDelete;
    }

    public function getModel(): ?MultipleTableDto
    {
        // if ($this->multipleTable) {
        //     return $this->multipleTable;
        // }

        if (in_array($this->multipleModel?->modelType, ['table', 'class'])) {
            return $this->multipleModel;
        }

        return null;
    }

    public function getOrderByColumn(): ?string
    {
        return $this->multipleModel?->orderableColumn;
    }

    //endregion

    //region GetterAndSetter

    public function setForm(Form $form)
    {
        $this->form = $form;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function getParentBluePrint()
    {
        return $this->parentBluePrint;
    }

    public function amIChildBluePrint()
    {
        return $this->parentBluePrint !== null;
    }

    public function getList(): array
    {
        $list = [];
        foreach ($this->dataTypeList as $group) {
            foreach ($group as $input) {
                $list[] = $input;
            }
        }

        return $list;
    }

    /**
     * Get array of Input Types.
     *
     * @return array<\Deep\FormTool\Core\InputTypes\BaseInputType>
     **/
    public function getInputList(): array
    {
        $list = [];
        foreach ($this->dataTypeList as $group) {
            foreach ($group as $input) {
                if (! $input instanceof InputTypes\HtmlType) {
                    $list[] = $input;
                }
            }
        }

        return $list;
    }

    public function getGroups(): array
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

        foreach ($this->dataTypeList as $group) {
            foreach ($group as $input) {
                if ($input instanceof TextType && ! $input->isEncrypted()) {
                    return $input->getDbField();
                }
            }
        }

        return null;
    }

    public function getSelectDbOptions($ignoreColumns = [])
    {
        $selects = [];
        foreach ($this->dataTypeList as $group) {
            foreach ($group as $input) {
                if ($input instanceof BluePrint || ! $input instanceof SelectType ||
                    // table name should be same as crud table, otherwise skip
                    ($input->getTableName() && $input->getTableName() != $this->form->getModel()->getTableName())) {
                    continue;
                }

                foreach ($input->getOptionData() as $options) {
                    foreach ($options as $type => $optionData) {
                        if ($type == 'db' && ! in_array($input->getDbField(), $ignoreColumns)) {
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

        return $selects;
    }

    public function getSelectDbOptionsForKeyValue()
    {
        $selects = [];
        foreach ($this->dataTypeList as $group) {
            foreach ($group as $input) {
                if ($input instanceof BluePrint || ! $input instanceof SelectType ||
                    // table name should be same as crud table, otherwise skip
                    ($input->getTableName() && $input->getTableName() != $this->form->getModel()->getTableName())) {
                    continue;
                }

                foreach ($input->getOptionData() as $options) {
                    foreach ($options as $type => $optionData) {
                        if ($type == 'db') {
                            $selects[] = (object) [
                                'table' => $optionData->table,
                                'column' => 'value',
                                'label' => $input->getLabel(),
                                'where' => ['key' => $input->getDbField()],
                            ];
                        }
                    }
                }
            }
        }

        return $selects;
    }

    //endregion

    public function __clone()
    {
        foreach ($this->dataTypeList as &$group) {
            foreach ($group as &$input) {
                $input = clone $input;

                if (! $input instanceof BluePrint) {
                    $input->init($this, $input->getDbField(), $input->getLabel());
                }
            }
        }
    }

    public function toObj($type)
    {
        $data['fields'] = [];
        foreach ($this->dataTypeList as $group) {
            foreach ($group as $fieldType) {
                $data['fields'][] = $fieldType->toObj($type);
            }
        }

        return $data['fields'];
    }
}
