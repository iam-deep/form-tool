<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Support\Arr;

class TableField
{
    public $cellList = [];
    public $actions = [];

    private Table $table;
    private BluePrint $bluePrint;

    public function __construct(Table $table)
    {
        $this->table = $table;
        $this->bluePrint = $this->table->getBluePrint();
    }

    public function default(string $dbField, string $label = null): CellDefinition
    {
        $input = $this->bluePrint->getInputTypeByDbField($dbField);
        if (! $input) {
            throw new \Exception($dbField.' not found in the BluePrint.');
        }

        $cell = CellDefinition::Input($input)->label($label);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function custom($class, string $dbField, string $label = null): CellDefinition
    {
        $inputType = new $class();

        if (! $inputType instanceof InputTypes\BaseInputType) {
            throw new \Exception($class.' should extends Biswadeep\FormTool\Core\InputTypes\BaseInputType');
        }

        if (! $inputType instanceof InputTypes\ICustomType) {
            throw new \Exception($class.' should implements Biswadeep\FormTool\Core\InputTypes\ICustomType');
        }

        $inputType->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($inputType);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function any($closureOrPattern, ...$dbFields): CellDefinition
    {
        $cell = CellDefinition::Any($closureOrPattern, $dbFields);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function text(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\TextType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function select(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\SelectType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function date(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\DateType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function time(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\TimeType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function datetime(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\DateTimeType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function status(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\SelectType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function image(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\ImageType();
        $type->init($this->bluePrint, $dbField, $label);

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

        $cell = CellDefinition::Other('action', '', 'Actions')->css('min-width:100px')->right()->orderable(false);
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

    public function bulkActionCheckbox(): CellDefinition
    {
        $cell = CellDefinition::Other('_bulk', '<input type="checkbox" class="selectAll">')->width('25px')->orderable(false);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function slNo(string $label = null): CellDefinition
    {
        $cell = CellDefinition::Other('_slno', $label ?? '#', '')->width('50px')->orderable(false);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function create(): array
    {
        return $this->columns;
    }
}
