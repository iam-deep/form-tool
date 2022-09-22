<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Support\Arr;

class TableField
{
    public $cellList = [];
    public $actions = [];

    private Table $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function slNo(string $label = null): CellDefinition
    {
        $cell = CellDefinition::Other('_slno', $label ?? '#', '')->width('50px');
        $this->cellList[] = $cell;

        return $cell;
    }

    public function default(string $dbField, string $label = null)
    {
        $input = $this->table->getBluePrint()->getInputTypeByDbField($dbField);
        if (! $input) {
            throw new \Exception($dbField.' not found in the BluePrint.');
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
        $actions = Arr::wrap($actions);

        foreach ($actions as $action) {
            if ($action == 'edit') {
                if (Guard::hasEdit()) {
                    $this->actions[] = new TableAction($action);
                }
            } elseif ($action == 'delete') {
                if (Guard::hasDelete()) {
                    $this->actions[] = new TableAction($action);
                }
            } else {
                $this->actions[] = new TableAction($action);
            }
        }

        $cell = CellDefinition::Other('action', '', 'Actions')->width('85px');
        if (\count($this->actions)) {
            $this->cellList['actions'] = $cell;
        }

        return $cell;
    }

    public function removeActions()
    {
        if (isset($this->cellList['actions'])) {
            unset($this->cellList['actions']);
        }

        return null;
    }

    public function create(): array
    {
        return $this->columns;
    }
}
