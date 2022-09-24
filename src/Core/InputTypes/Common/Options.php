<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

use Biswadeep\FormTool\Core\InputTypes\InputType;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;

trait Options
{
    protected $optionData = [];
    protected bool $isMultiple = false;

    // Desired values: InputType::Select, InputType::Checkbox, InputType::Radio
    protected int $optionType = -1;
    protected $options = null;

    protected int $limitMin = 0;
    protected int $limitMax = 0;

    protected bool $isRemoveTrash = true;

    //region Setter
    public function options($options, ...$patternDbFields)
    {
        if (\is_string($options)) {
            $db = \explode('.', $options);

            $tableInfo = new \stdClass();
            if (\count($db) >= 3) {
                $tableInfo->dbTable = \trim($db[0]);
                $tableInfo->dbTableValue = \trim($db[1]);
                $tableInfo->dbTableTitle = \trim($db[2]);
                $tableInfo->dbPatternFields = $patternDbFields;
            } else {
                throw new \Exception('Wrong format! It should be table_name.value_column.text_column');
            }

            $this->optionData[] = ['db' => $tableInfo];
        } elseif (\is_array($options)) {
            $this->optionData[] = ['array' => $options];
        } else {
            throw new \Exception('You need to pass an array or string with table info');
        }

        return $this;
    }

    public function withoutTrash(bool $flag = true)
    {
        $this->isRemoveTrash = $flag;

        return $this;
    }

    public function multiple()
    {
        $this->isMultiple = true;

        return $this;
    }

    public function min(int $min)
    {
        if ($min > 0) {
            $this->validations[] = 'min:'.$min;
            $this->limitMin = $min;

            if (! isset($this->validationMessages['min'])) {
                $this->validationMessages['min'] = sprintf('The %s must be at least %s items.', $this->label, $min);
            }
        } else {
            throw new \Exception('min value must be greater than 0 for field: '.$this->dbField);
        }

        return $this;
    }

    public function max(int $max)
    {
        if ($max > 0) {
            $this->validations[] = 'max:'.$max;
            $this->limitMax = $max;

            if (! isset($this->validationMessages['max'])) {
                $this->validationMessages['max'] = sprintf('The %s must not be greater than %s items.', $this->label, $max);
            }
        } else {
            throw new \Exception('max value must be greater than 0 for field: '.$this->dbField);
        }

        return $this;
    }
    //endregion

    protected function createOptions()
    {
        if ($this->options) {
            return;
        }

        $metaColumns = \config('form-tool.table_meta_columns');

        $this->options = new \stdClass();
        if ($this->optionData) {
            foreach ($this->optionData as $optionData) {
                foreach ($optionData as $type => $options) {
                    if ('db' == $type) {
                        $query = DB::table($options->dbTable);
                        if ($this->isRemoveTrash) {
                            $query->whereNull($metaColumns['deletedAt'] ?? 'deletedAt');
                        }
                        $result = $query->orderBy($options->dbTableValue)->get();

                        foreach ($result as $row) {
                            $text = '';
                            if ($options->dbPatternFields) {
                                $values = [];
                                foreach ($options->dbPatternFields as $field) {
                                    $field = \trim($field);
                                    if (\property_exists($row, $field)) {
                                        $values[] = $row->{$field};
                                    } else {
                                        $values[] = 'DB field "'.$field.'" not exists!';
                                    }
                                }

                                $text = \vsprintf($options->dbTableTitle, $values);
                            } else {
                                $text = $row->{$options->dbTableTitle};
                            }

                            $val = $row->{$options->dbTableValue};
                            $this->options->{$val} = $text;
                        }
                    } else {
                        foreach ($options as $val => $text) {
                            $this->options->{$val} = $text;
                        }
                    }
                }
            }
        }

        if ($this->optionType == InputType::Checkbox) {
            $totalOptions = \count((array) $this->options);
            if ($totalOptions <= 1) {
                if ($totalOptions == 0) {
                    $this->singleOptions[$this->valueYes] = $this->captionYes;
                    $this->options->{$this->valueYes} = $this->captionYes;
                } elseif (\property_exists($this->options, '0')) {
                    // We cannot accept Yes/On value as 0 for single option, so let's change it
                    $this->options->{$this->valueYes} = $this->captionYes = $this->options->{'0'};
                    unset($this->options->{'0'});
                }
            }
        }
    }

    // This function have not used in checkbox
    protected function countOptions()
    {
        if ($this->options) {
            return \count((array) $this->options);
        }

        $this->createOptions();

        return \count((array) $this->options);
    }

    public function getTableValue()
    {
        if ($this->value === null) {
            return null;
        }

        $this->withoutTrash(false);
        $this->createOptions();

        if ($this->isMultiple) {
            $values = [];
            $rawValues = (array) \json_decode($this->value, true);
            $i = 0;
            foreach ($rawValues as $val) {
                $values[] = $this->options->{$val} ?? null;

                if ($i++ >= 2) {
                    break;
                }
            }

            return implode(', ', $values).(\count($rawValues) > 3 ? '...' : '');
        } else {
            if ($this->optionType == InputType::Checkbox && $this->singleOptions) {
                return $this->value == $this->valueYes ? $this->captionYes : $this->captionNo;
            }

            return $this->options->{$this->value} ?? null;
        }
    }
}
