<?php

namespace Biswadeep\FormTool\Core\InputTypes\Common;

use Biswadeep\FormTool\Core\DataModel;
use Biswadeep\FormTool\Core\Doc;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Closure;

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

    protected ?string $dependField = null;
    protected ?string $dependColumn = null;
    protected ?string $dependValue = null;

    //region Setter
    public function options($options, ...$patternDbFields)
    {
        if (\is_string($options)) {
            $db = \explode('.', $options);

            $tableInfo = new \stdClass();
            if (\count($db) >= 3) {
                $tableInfo->table = \trim($db[0]);
                $tableInfo->valueCol = \trim($db[1]);
                $tableInfo->textCol = \trim($db[2]);
                $tableInfo->orderByCol = \trim($db[3] ?? null);
                $tableInfo->orderByDirection = \trim($db[4] ?? 'asc');
                $tableInfo->dbPatternFields = $patternDbFields;
            } else {
                throw new \Exception('Wrong format! It should be tableName.valueColumn.textColumn[.orderByColumn[.orderDirection]]');
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

    public function depend($field, $foreignKey = null)
    {
        $this->dependField = trim($field);
        $this->dependColumn = trim($foreignKey);

        if (! $foreignKey) {
            $this->dependColumn = $this->dependField;
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
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        $this->options = new \stdClass();
        if ($this->optionData) {
            foreach ($this->optionData as $optionData) {
                foreach ($optionData as $type => $options) {
                    if ('db' == $type) {
                        $where = [];
                        if ($this->dependField) {
                            if (! $this->dependValue) {
                                $this->dependValue = $this->bluePrint->getInputTypeByDbField($this->dependField)->getValue();
                            }
                            if (! $this->dependValue) {
                                continue;
                            }
                            $where[] = [$this->dependColumn => $this->dependValue];
                        }

                        if (isset($options->dbPatternFields[0])) {
                            $flag = false;
                            $condition = $options->dbPatternFields[0];
                            if ($condition instanceof Closure) {
                                $where[] = $condition;
                                $flag = true;
                            } elseif ($condition && \is_string($condition[array_key_first($condition)])) {
                                $where[] = $condition;
                                $flag = true;
                            } elseif ($condition) {
                                $where = array_merge($where, $condition);
                                $flag = true;
                            }

                            if ($flag) {
                                array_shift($options->dbPatternFields);
                            }
                        }

                        if ($this->isRemoveTrash) {
                            $where[] = function ($query) use ($deletedAt) { $query->whereNull($deletedAt); };
                        }

                        $model = (new DataModel())->db($options->table);
                        $result = $model->getWhere($where, $options->orderByCol, $options->orderByDirection);
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

                                $text = \vsprintf($options->textCol, $values);
                            } else {
                                $text = $row->{$options->textCol};
                            }

                            $val = $row->{$options->valueCol};
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

    protected function addScript()
    {
        if (! $this->dependField) {
            return;
        }

        $input = new \stdClass();
        $input->field = $this->dbField;
        $input->dependField = $this->dependField;
        $input->isChosen = $this->currentPlugin == 'chosen';
        $input->route = $this->bluePrint->getForm()->getResource()->route;

        $data['input'] = $input;

        Doc::addJs(\view('form-tool::form.scripts.select_depend', $data), $this->dbField.'-depend');
    }

    public function getChildOptions($parentId)
    {
        $this->dependValue = $parentId;

        $options = $this->getDependOptions();

        return $options;
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

    public function getNiceValue($value)
    {
        if ($value === null && $this->optionType != InputType::Checkbox) {
            return null;
        }

        $this->withoutTrash(false);
        $this->createOptions();

        if ($this->isMultiple) {
            $values = [];

            if (\is_array($value)) {
                $rawValues = $value;
            } else {
                $rawValues = (array) \json_decode($value, true);
            }

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
                return $value == $this->valueYes ? $this->captionYes : $this->captionNo;
            }

            return $this->options->{$value} ?? null;
        }
    }

    public function getOptionData()
    {
        return $this->optionData;
    }
}
