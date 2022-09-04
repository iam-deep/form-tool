<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;

trait Options
{
    public string $dbTable = '';
    public string $dbTableValue = '';
    public string $dbTableTitle = '';
    public $dbPatternFields = [];

    public $options = [];
    public $result = null;

    public string $optionType = '';
    public int $countOption = 0;

    public bool $isMultiple = false;
    public int $limitMultiple = 0; 

    public function options($options, ...$patternDbFields)
    {
        if (\is_string($options)) {
            $db = \explode('.', $options);

            if (\count($db) >= 3) {
                $this->dbTable = \trim($db[0]);
                $this->dbTableValue = \trim($db[1]);
                $this->dbTableTitle = \trim($db[2]);
                $this->dbPatternFields = $patternDbFields;
            } else {
                throw new \Exception('Wrong format! It should be table_name.value_column.text_column');
            }

            $this->result = DB::table($this->dbTable)->orderBy($this->dbTableValue)->get();

            $this->optionType = 'db';
            $this->countOption = \count($this->result);
        }
        elseif (\is_array($options)) {
            $this->options = $options;

            $this->optionType = 'array';
            $this->countOption = \count($this->options);
        }

        return $this;
    }

    public function getTableValue()
    {
        if ($this->isMultiple) {
            if ($this->value) {
                $values = [];
                $rawValues = (array)\json_decode($this->value, true);
                foreach ($rawValues as $val) {
                    $values[] = $this->options[$val] ?? '';
                }

                return implode(', ', $values);
            }
        }
        else if (isset($this->options[$this->value])) {
            return $this->options[$this->value];
        }

        if ($this->result) {
            foreach ($this->result as $row) {
                if ($row->{$this->dbTableValue} === $this->value) {
                    if ($this->dbPatternFields) {
                        $values = [];
                        foreach ($this->dbPatternFields as $field) {
                            $field = \trim($field);
                            if (\property_exists($row, $field)) {
                                $values[] = $row->{$field};
                            } else {
                                $values[] = '<b class="text-red">DB field "'.$field.'" not exists!</b>';
                            }
                        }

                        return \vsprintf($this->dbTableTitle, $values);
                    }

                    return $row->{$this->dbTableTitle};
                }
            }
        }

        return null;
    }

    public function multiple($limit = 0)
    {
        $this->isMultiple = true;
        $this->limitMultiple = $limit;
    }
}
