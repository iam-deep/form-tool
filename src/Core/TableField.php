<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Support\Arr;

class TableField
{
    public $cellList = [];
    private $actionButtons = [];
    public $primaryButtonName = null;

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

    /**
     * Create action buttons for table list
     *
     * @param array[string|\Biswadeep\FormTool\Core\Button] $buttons
     * @param string $primaryButtonName "name" of the primary dropdown button (Default is: _first_button except delete)
     * @return CellDefinition
     * @throws \Exception
     **/
    public function actions($buttons = ['edit', 'delete'], ?string $primaryButtonName = '_first_button'): CellDefinition
    {
        $buttons = Arr::wrap($buttons);
        $this->primaryButtonName = $primaryButtonName;

        $this->actionButtons = [];
        foreach ($buttons as $button) {
            if ($button == 'edit') {
                $newButton = Button::makeEdit();
                if ($newButton->isActive()) {
                    $this->actionButtons[] = $newButton;
                }
            } elseif ($button == 'delete') {
                $newButton = Button::makeDelete();
                if ($newButton->isActive()) {
                    $this->actionButtons[] = $newButton;
                }
            } elseif ($button == 'divider') {
                $newButton = Button::makeDivider();
                if ($newButton->isActive()) {
                    $this->actionButtons[] = $newButton;
                }
            } elseif ($button instanceof Button) {
                if ($button->isActive()) {
                    $this->actionButtons[] = $button;
                }
            } else {
                throw new \Exception(\sprintf('Button can be "edit", "delete", "divider" or an instance of "%s"', Button::class));
            }
        }

        $cell = CellDefinition::Other('action', '', 'Actions')->css('min-width:100px')->right()->orderable(false);
        if ($this->actionButtons) {
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

    public function getActionButtons()
    {
        $primary = null;
        $secondaries = [];
        foreach ($this->actionButtons as $button) {
            if (! $primary && ! $button->isDivider()) {
                if ($this->primaryButtonName == '_first_button' && $button->getGuard() != 'delete') {
                    $primary = $button;
                    continue;
                } elseif ($this->primaryButtonName == $button->getName()) {
                    $primary = $button;
                    continue;
                }
            }

            $secondaries[] = $button;
        }

        return ['primary' => $primary, 'secondaries' => $secondaries];
    }
}
