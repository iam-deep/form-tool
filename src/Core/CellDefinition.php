<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\BaseInputType;
use Closure;

class CellDefinition
{
    private BaseInputType $inputType;

    public string $fieldType;
    private ?string $dbField = null;
    private ?string $label = '';

    private bool $orderable = true;
    private ?string $orderByColumn = null;

    // Only for Table Header Info
    public bool $isOrdered = false;
    public ?string $direction = null;
    public ?string $orderUrl = null;

    private $concat = null;
    private $anyPattern = null;
    private $anyDbFields = null;

    // Styles
    private string $width = '';
    private string $align = '';
    private string $styleCSS = '';
    private $styleClass = [];
    public $raw = '';

    // Private is important
    private function __construct()
    {
    }

    public static function Input(BaseInputType $inputType): CellDefinition
    {
        $cell = new CellDefinition();

        $cell->fieldType = '_input';
        $cell->inputType = $inputType;

        return $cell;
    }

    public static function Other(string $fieldType, string $dbField, string $label = null): CellDefinition
    {
        $cell = new CellDefinition();

        $cell->fieldType = $fieldType;
        $cell->dbField = $dbField;
        $cell->label = $label ?: \ucfirst($dbField);

        return $cell;
    }

    public static function Any($pattern, $dbFields): CellDefinition
    {
        $cell = new CellDefinition();

        $cell->fieldType = '_any';
        $cell->concat = new \stdClass();
        $cell->concat->pattern = $pattern;
        $cell->concat->dbFields = $dbFields;

        $cell->orderable = false;

        return $cell;
    }

    public function typeOptions(Closure $options): CellDefinition
    {
        $options($this->inputType);

        return $this;
    }

    public function orderable($column = null): CellDefinition
    {
        if ($column === false) {
            $this->orderable = false;

            return $this;
        }

        $this->orderable = true;
        $this->orderByColumn = $column;

        return $this;
    }

    public function right(): CellDefinition
    {
        $this->styleClass[] = 'text-right';

        return $this;
    }

    public function left(): CellDefinition
    {
        $this->styleClass[] = 'text-left';

        return $this;
    }

    public function center(): CellDefinition
    {
        $this->styleClass[] = 'text-center';

        return $this;
    }

    public function width($width): CellDefinition
    {
        $this->width = \trim($width);

        return $this;
    }

    public function label($label): CellDefinition
    {
        $this->label = \trim($label);

        return $this;
    }

    public function concat($pattern = '', ...$dbFields): CellDefinition
    {
        $this->concat = new \stdClass();
        $this->concat->pattern = $pattern;
        $this->concat->dbFields = $dbFields;

        return $this;
    }

    public function raw(string $rawAttributes): CellDefinition
    {
        $this->raw .= $rawAttributes.' ';

        return $this;
    }

    public function setup(): void
    {
        $this->styleCSS = '';
        if ($this->align) {
            $this->styleCSS .= $this->align;
        }

        if ($this->width) {
            $this->styleCSS .= 'width:'.$this->width.';';
        }

        if ($this->styleCSS) {
            $this->styleCSS = 'style="'.$this->styleCSS.'"';
        }

        $this->styleClass = \implode(' ', $this->styleClass);
        if ($this->styleClass) {
            $this->styleClass = 'class="'.$this->styleClass.'"';
        }

        $this->raw($this->styleCSS);
        $this->raw($this->styleClass);
    }

    // Getter

    public function getInputType()
    {
        return $this->inputType ?? null;
    }

    public function getLabel()
    {
        if ($this->label) {
            return $this->label;
        }

        if (isset($this->inputType)) {
            return $this->inputType->getLabel();
        }

        return null;
    }

    public function getDbField()
    {
        if ($this->fieldType == '_input') {
            return $this->inputType->getDbField();
        }

        return $this->dbField;
    }

    public function setValue($value)
    {
        if ($this->fieldType == '_input') {
            return $this->inputType->setValue($value);
        }
    }

    public function getValue()
    {
        if ($this->fieldType == '_input') {
            return $this->inputType->getTableValue();
        }

        return null;
    }

    public function getConcat()
    {
        return $this->concat;
    }

    public function isOrderable()
    {
        return $this->orderable;
    }

    public function getOrderByColumn()
    {
        if ($this->orderByColumn) {
            return $this->orderByColumn;
        }

        return $this->getDbField();
    }

    public function __call($method, $parameters): CellDefinition
    {
        $this->inputType->{$method}(...$parameters);

        return $this;
    }
}

class TableAction
{
    public string $action = '';

    public function __construct($action)
    {
        $this->action = $action;
    }
}
