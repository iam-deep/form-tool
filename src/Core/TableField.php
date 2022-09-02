<?php

namespace Biswadeep\FormTool\Core;

class TableField
{
    public $cellList = [];
    public $actions = [];

    private Table $_table;

    public function __construct(Table $table)
    {
        $this->_table = $table;
    }

    public function slNo(string $label = null): CellDefinition
    {
        $cell = CellDefinition::Other('_slno', $label ?? '#', '')->width('50px');
        $this->cellList[] = $cell;

        return $cell;
    }

    public function default(string $dbField, string $label = null)
    {
        $input = $this->_table->getDataModel()->getInputTypeByDbField($dbField);
        if (! $input) {
            throw new \Exception($dbField.' not found in the DataModel.');
        }

        $cell = CellDefinition::Input($input)->label($label);
        $this->cellList[] = $cell;

        return $cell;
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

        $inputType->init(null, $dbField, $label);

        $cell = CellDefinition::Input($inputType);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function text(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\TextType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function select(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\SelectType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function date(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\DateType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function time(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\TimeType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function datetime(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\DateTimeType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function status(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\SelectType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function image(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\ImageType();
        $type->init(null, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function actions($actions = ['edit', 'delete']): CellDefinition
    {
        if (! is_array($actions)) {
            throw new \Exception("Actions columns should be in an array! Like: ['edit', 'delete']");
        }

        $cell = CellDefinition::Other('action', '', 'Actions')->width('85px');
        $this->cellList[] = $cell;

        foreach ($actions as $action) {
            $this->actions[] = new TableAction($action);
        }

        return $cell;
    }

    public function create(): array
    {
        return $this->columns;
    }
}
