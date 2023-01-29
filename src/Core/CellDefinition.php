<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Core\InputTypes\BaseInputType;
use Closure;

class CellDefinition
{
    private TableField $tableField;
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
    private string $styleCss = '';
    private $styleClass = [];
    public $raw = '';

    private function __construct()
    {
        // The construct must remain private
    }

    public static function Input(TableField $tableField, BaseInputType $inputType): CellDefinition
    {
        $cell = new CellDefinition();
        $cell->tableField = $tableField;

        $cell->fieldType = '_input';
        $cell->inputType = $inputType;

        return $cell;
    }

    public static function Other(
        TableField $tableField,
        string $fieldType,
        string $dbField,
        string $label = null
    ): CellDefinition {
        $cell = new CellDefinition();
        $cell->tableField = $tableField;

        $cell->fieldType = $fieldType;
        $cell->dbField = $dbField;
        $cell->label = $label ?: \ucfirst($dbField);

        return $cell;
    }

    public static function Any(TableField $tableField, $pattern, $dbFields): CellDefinition
    {
        $cell = new CellDefinition();
        $cell->tableField = $tableField;

        $cell->fieldType = '_any';
        $cell->concat = new \stdClass();
        $cell->concat->pattern = $pattern;
        $cell->concat->dbFields = $dbFields;

        $cell->orderable = false;

        return $cell;
    }

    public function setTableField(TableField $tableField)
    {
        $this->tableField = $tableField;
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
        $this->styleClass[] = \config('form-tool.styleClass.text-right', 'text-right');

        return $this;
    }

    public function left(): CellDefinition
    {
        $this->styleClass[] = \config('form-tool.styleClass.text-left', 'text-left');

        return $this;
    }

    public function center(): CellDefinition
    {
        $this->styleClass[] = \config('form-tool.styleClass.text-center', 'text-center');

        return $this;
    }

    public function width($width): CellDefinition
    {
        $this->width = \trim($width);

        return $this;
    }

    public function css($css)
    {
        $this->styleCss .= $css;

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

    /**
     * Only for actions method.
     */
    public function moreButton($name, $showMoreButtonAlways = false)
    {
        $this->tableField->moreButton($name, $showMoreButtonAlways);

        return $this;
    }

    public function setup(): void
    {
        $styleCss = '';
        if ($this->align) {
            $styleCss .= $this->align;
        }

        if ($this->width) {
            $styleCss .= 'width:'.$this->width.';';
        }

        if ($this->styleCss) {
            $styleCss .= $this->styleCss;
        }

        if ($styleCss) {
            $styleCss = 'style="'.$styleCss.'"';
        }

        $this->styleClass = \implode(' ', $this->styleClass);
        if ($this->styleClass) {
            $this->styleClass = 'class="'.$this->styleClass.'"';
        }

        $this->raw($styleCss);
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
