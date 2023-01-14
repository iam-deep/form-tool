<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Support\Arr;

class TableField
{
    public $cellList = [];

    private $actionButtons = [];
    private $primaryButtonName = null;
    private $moreButtonName = '';
    private bool $showMoreButtonAlways = false;

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
            throw new \InvalidArgumentException($dbField.' not found in the BluePrint.');
        }

        $cell = CellDefinition::Input($this, $input)->label($label);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function custom($class, string $dbField, string $label = null): CellDefinition
    {
        $inputType = new $class();

        if (! $inputType instanceof InputTypes\BaseInputType) {
            throw new \InvalidArgumentException(\sprintf(
                '%s should extends Biswadeep\FormTool\Core\InputTypes\BaseInputType',
                $class
            ));
        }

        if (! $inputType instanceof InputTypes\ICustomType) {
            throw new \InvalidArgumentException(\sprintf(
                '%s should implements Biswadeep\FormTool\Core\InputTypes\ICustomType',
                $class
            ));
        }

        $inputType->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $inputType);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function any($closureOrPattern, ...$dbFields): CellDefinition
    {
        $cell = CellDefinition::Any($this, $closureOrPattern, $dbFields);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function text(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\TextType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function select(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\SelectType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function date(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\DateType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function time(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\TimeType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function datetime(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\DateTimeType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $type);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function image(string $dbField, string $label = null): CellDefinition
    {
        $type = new InputTypes\ImageType();
        $type->init($this->bluePrint, $dbField, $label);

        $cell = CellDefinition::Input($this, $type);
        $this->cellList[] = $cell;

        return $cell;
    }

    /**
     * Create action buttons for table list.
     *
     * @param array[string|\Biswadeep\FormTool\Core\Button] $buttons
     * @param  string  $primaryButtonName  "name" of the primary dropdown button (Default is: _first_button except delete)
     * @return CellDefinition
     *
     * @throws \InvalidArgumentException
     **/
    public function actions($buttons = ['edit', 'delete'], ?string $primaryButtonName = '_first_button'): CellDefinition
    {
        $buttons = Arr::wrap($buttons);
        $this->primaryButtonName = $primaryButtonName;

        $this->actionButtons = [];
        foreach ($buttons as $button) {
            if ($button == 'edit') {
                $this->actionButtons[] = Button::makeEdit();
            } elseif ($button == 'delete') {
                $this->actionButtons[] = Button::makeDelete();
            } elseif ($button == 'divider') {
                $this->actionButtons[] = Button::makeDivider();
            } elseif ($button instanceof Button) {
                $this->actionButtons[] = $button;
            } else {
                throw new \InvalidArgumentException(\sprintf(
                    'Button can be "edit", "delete", "divider" or an instance of "%s"',
                    Button::class
                ));
            }
        }

        $cell = CellDefinition::Other($this, 'action', '', 'Actions')->css('min-width:100px')
            ->right()->orderable(false);
        if ($this->actionButtons) {
            $this->cellList['actions'] = $cell;
        }

        return $cell;
    }

    public function moreButton($name, $showMoreButtonAlways = false)
    {
        $this->moreButtonName = $name;
        $this->showMoreButtonAlways = $showMoreButtonAlways;

        return $this;
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
        $cell = CellDefinition::Other($this, '_bulk', '<input type="checkbox" class="selectAll">')
            ->width('25px')->orderable(false);
        $this->cellList[] = $cell;

        return $cell;
    }

    public function slNo(string $label = null): CellDefinition
    {
        $cell = CellDefinition::Other($this, '_slno', $label ?? '#', '')->width('50px')->orderable(false);
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
            if (! $button->isActive()) {
                continue;
            }

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

        return (object) ['primary' => $primary, 'secondaries' => $secondaries, 'more' => (object) [
            'name' => $this->moreButtonName,
            'isActive' => $this->moreButtonName && ($this->showMoreButtonAlways || ! $primary),
        ]];
    }

    public function getTable()
    {
        return $this->table;
    }

    public function getBlueprint()
    {
        return $this->bluePrint;
    }
}
