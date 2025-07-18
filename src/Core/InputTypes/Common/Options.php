<?php

namespace Deep\FormTool\Core\InputTypes\Common;

use Closure;
use Deep\FormTool\Core\DataModel;
use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Facades\Cache;

trait Options
{
    protected $optionData = [];
    protected bool $isMultiple = false;

    // Desired values: InputType::SELECT, InputType::CHECKBOX, InputType::RADIO
    protected int $optionType = -1;
    protected $options = null;

    protected int $limitMin = 0;
    protected int $limitMax = 0;

    protected bool $isRemoveTrash = true;

    protected $depend = [];

    protected $cacheKey = null;
    protected int $cacheExpiry = 0;

    //region Setter
    public function options($options, ...$patternDbFields)
    {
        if (\is_string($options)) {
            $db = array_values(\explode('.', $options));

            $tableInfo = new \stdClass();
            if (\count($db) >= 3) {
                $tableInfo->table = \trim($db[0]);
                $tableInfo->valueCol = \trim($db[1]);
                $tableInfo->textCol = \trim($db[2]);
                $tableInfo->orderByCol = \trim($db[3] ?? null);
                $tableInfo->orderByDirection = \trim($db[4] ?? 'asc');
                $tableInfo->dbPatternFields = $patternDbFields;
            } else {
                throw new \InvalidArgumentException(
                    'Wrong format! It should be "tableName.valueColumn.textColumn[.orderByColumn[.orderDirection]]"'
                );
            }

            $this->optionData[] = ['db' => $tableInfo];
        } elseif (\is_array($options)) {
            $this->optionData[] = ['array' => $options];
        } else {
            throw new \InvalidArgumentException('You need to pass an array or string with table info');
        }

        return $this;
    }

    public function closure(Closure $closure, $columns = null, ...$patternDbFields)
    {
        $info = new \stdClass();
        $info->closure = $closure;
        $info->valueCol = 'value';
        $info->textCol = 'text';
        $info->dbPatternFields = $patternDbFields;

        if ($columns !== null) {
            $cols = array_values(\explode('.', $columns));
            if (count($cols) >= 2) {
                $info->valueCol = $cols[0];
                $info->textCol = $cols[1];
            } else {
                throw new \InvalidArgumentException('Wrong format! It should be "valueColumn.textColumn"');
            }
        }

        $this->optionData[] = ['closure' => $info];

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
            throw new \InvalidArgumentException('min value must be greater than 0 for field: '.$this->dbField);
        }

        return $this;
    }

    public function max(int $max)
    {
        if ($max > 0) {
            $this->validations[] = 'max:'.$max;
            $this->limitMax = $max;

            if (! isset($this->validationMessages['max'])) {
                $this->validationMessages['max'] = sprintf(
                    'The %s must not be greater than %s items.',
                    $this->label,
                    $max
                );
            }
        } else {
            throw new \InvalidArgumentException('max value must be greater than 0 for field: '.$this->dbField);
        }

        return $this;
    }

    public function depend($field, $foreignKey = null, $bluePrint = null)
    {
        $field = \trim($field);
        if (isset($this->depend[$field])) {
            throw new \InvalidArgumentException(\sprintf('Depend field "%s" is already exists!', $field));
        }

        $this->depend[$field] = (object) [
            'field' => $field,
            'column' => trim($foreignKey) ?: $field,
            'value' => null,
            'bluePrint' => $bluePrint,
        ];

        return $this;
    }

    public function dependParent($field, $foreignKey = null)
    {
        $field = \trim($field);
        if (isset($this->depend[$field])) {
            throw new \InvalidArgumentException(\sprintf('Depend field "%s" is already exists!', $field));
        }

        $this->depend[$field] = (object) [
            'field' => $field,
            'column' => trim($foreignKey) ?: $field,
            'value' => null,
            'bluePrint' => $this->bluePrint->getParentBluePrint(),
        ];

        return $this;
    }

    public function cache($key, $expiry = null)
    {
        $this->cacheKey = $key;
        $this->cacheExpiry = $expiry ?? config('form-tool.defaultCacheExpiry', 60 * 60 * 24);

        return $this;
    }
    //endregion

    protected function createOptions($skipDepend = false)
    {
        // We need to fetch for every row if we have some depended field in multiple table
        if ($this->options && (! $this->bluePrint->isMultiple || ! $this->depend)) {
            return;
        }

        $metaColumns = \config('form-tool.table_meta_columns');
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        $this->options = new \stdClass();
        if ($this->optionData) {
            foreach ($this->optionData as $optionData) {
                foreach ($optionData as $type => $options) {
                    if ('db' == $type || 'closure' == $type) {
                        $where = [];
                        $dependValue = null;

                        // We are skipping depend value at the time of table listing to get all the values at once
                        // Otherwise we need to create option every time for each dependent value
                        if ($this->depend && ! $skipDepend) {
                            $flagHaveDependValue = false;
                            foreach ($this->depend as &$depend) {
                                if (isNullOrEmpty($depend->value)) {
                                    $dependInput = null;
                                    if ($depend->bluePrint) {
                                        $bluePrint = $depend->bluePrint;
                                        $dependInput = $bluePrint->getInputTypeByDbField($depend->field);
                                    } else {
                                        $dependInput = $this->bluePrint->getInputTypeByDbField($depend->field);
                                    }

                                    if (! $dependInput) {
                                        throw new FormToolException(sprintf('Depended field not found: %s', $depend->field));
                                    }

                                    if ($dependInput->isMultiple) {
                                        throw new FormToolException(sprintf('Depended field cannot be multiple: %s', $depend->field));
                                    }

                                    $depend->value = $dependInput->getValue();

                                    if (isNullOrEmpty($depend->value)) {
                                        continue;
                                    }
                                }

                                if (! $flagHaveDependValue) {
                                    $flagHaveDependValue = true;
                                }

                                $dependValue = $depend->value;
                                $where[] = [$depend->column => $depend->value];

                                // Let's reset the dependValue, so that we can fetch the new options for
                                // depended field in multiple table and at the time of import
                                if ($this->bluePrint->amIChildBluePrint() || $this->bluePrint->getForm()->getCrud()->getCurrentState() == CrudState::IMPORT) {
                                    $depend->value = null;
                                }
                            }

                            if (! $flagHaveDependValue) {
                                if (! isset($this->firstOption)) {
                                    $this->firstOption = new \stdClass();
                                    $this->firstOption->text = '(select '.\strtolower($dependInput->getLabel())
                                        .' first)';
                                    $this->firstOption->value = '';
                                }

                                continue;
                            }
                        }

                        if (isset($options->dbPatternFields[0]) && ! \is_string($options->dbPatternFields[0])) {
                            $flag = false;
                            $condition = $options->dbPatternFields[0];
                            if ($condition instanceof Closure ||
                                $condition && \is_string($condition[array_key_first($condition)])) {
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
                            // This was converted to array from closure to make it simple for closure as option
                            $where[] = [$deletedAt => null];
                            /*$where[] = function ($query) use ($deletedAt) {
                                $query->whereNull($deletedAt);
                            };*/
                        }

                        $result = null;
                        if ('closure' == $type) {
                            // Let's convert multi-d array to associative array for closure where simplicity
                            $temp = $where;
                            $where = [];
                            foreach ($temp as $w) {
                                foreach ($w as $col => $colValue) {
                                    if (\is_string($col)) {
                                        $where[$col] = $colValue;
                                    }
                                }
                            }

                            $closure = $options->closure;
                            $result = $closure($where);
                            if (! $result instanceof \Illuminate\Support\Collection) {
                                $type = gettype($result);
                                throw new FormToolException(\sprintf(
                                    'Return value of the %s\'s closure must be %s. Currently returned: %s',
                                    $this->dbField,
                                    \Illuminate\Support\Collection::class,
                                    $type == 'object' ? get_class($result) : $type
                                ));
                            }
                        } else {
                            if ($this->cacheKey) {
                                $cacheKey = $this->cacheKey;
                                if ($cacheKey instanceof Closure) {
                                    $cacheKey = $cacheKey($dependValue);
                                    if (! is_string($cacheKey)) {
                                        throw new FormToolException('Return value of the cache closure must be string!');
                                    }
                                }

                                $result = Cache::remember($cacheKey, $this->cacheExpiry, function () use ($where, $options) {
                                    return $this->getOptionsFromDB($where, $options);
                                });
                            } else {
                                $result = $this->getOptionsFromDB($where, $options);
                            }
                        }

                        if ($result && $result->count() > 0) {
                            if (! property_exists($result[0], $options->valueCol)) {
                                if ('db' == $type) {
                                    throw new \InvalidArgumentException(\sprintf(
                                        'Column "%s" not found in "%s" table',
                                        $options->valueCol,
                                        $options->table
                                    ));
                                } else {
                                    throw new \InvalidArgumentException(\sprintf(
                                        'Column "%s" not found in closure\'s response of "%s"',
                                        $options->valueCol,
                                        $this->dbField
                                    ));
                                }
                            }
                            if (! $options->dbPatternFields && ! property_exists($result[0], $options->textCol)) {
                                if ('db' == $type) {
                                    throw new \InvalidArgumentException(\sprintf(
                                        'Column "%s" not found in "%s" table',
                                        $options->textCol,
                                        $options->table
                                    ));
                                } else {
                                    throw new \InvalidArgumentException(\sprintf(
                                        'Column "%s" not found in closure\'s response of "%s"',
                                        $options->textCol,
                                        $this->dbField
                                    ));
                                }
                            }
                        }

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

        if ($this->optionType == InputType::CHECKBOX) {
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

    private function getOptionsFromDB($where, $options)
    {
        $model = (new DataModel())->db($options->table);

        // Applying order by default with the text column, if the text column is not pattern
        if (! $options->orderByCol && ! $options->dbPatternFields) {
            $options->orderByCol = $options->textCol;
        }

        return $model->getWhere($where, $options->orderByCol, $options->orderByDirection);
    }

    protected function addScript()
    {
        if (! $this->depend) {
            return;
        }

        $allDependFields = [];
        if (! $this->bluePrint->isMultiple) {
            foreach ($this->depend as $value) {
                $allDependFields[] = $value->field;
            }
        } else {
            foreach ($this->depend as $value) {
                $count = 1;
                foreach ($this->bluePrint->getInputList() as $field) {
                    if ($field->getDbField() == $value->field) {
                        break;
                    }

                    $count++;
                }

                $allDependFields[] = (object) [
                    'field' => $value->field,
                    'selectorChildCount' => $count,
                ];
            }
        }

        foreach ($this->depend as $depend) {
            $input = new \stdClass();
            $input->field = $this->dbField;
            $input->dependField = $depend->field;
            $input->isChosen = $this->currentPlugin == 'chosen';
            $input->route = $this->bluePrint->getForm()->getResource()->route;
            $input->allDependFields = $allDependFields;

            $input->isFirstOption = $this->isFirstOption;
            if (! isset($this->firstOption)) {
                $dependInput = null;
                if ($depend->bluePrint) {
                    $bluePrint = $depend->bluePrint;
                    $dependInput = $bluePrint->getInputTypeByDbField($depend->field);
                } else {
                    $dependInput = $this->bluePrint->getInputTypeByDbField($depend->field);
                }

                if (! $dependInput) {
                    throw new FormToolException(sprintf('Depended field not found: %s', $depend->field));
                }

                $input->firstOptionText = '(select '.\strtolower($dependInput->getLabel()).' first)';
                $input->firstOptionValue = '';
            } else {
                $input->firstOptionValue = $this->firstOption->value;
                $input->firstOptionText = $this->firstOption->text;
            }

            $key = $input->field.'-'.$input->dependField;
            $scriptFilename = 'select_depend';

            if ($this->bluePrint->isMultiple) {
                $input->multipleKey = $this->bluePrint->getKey();

                $key = $input->multipleKey.'-'.$key;
                $scriptFilename = 'select_depend_multiple';
            }

            $data['state'] = $this->bluePrint->getForm()->getCrud()->getCurrentState();
            $data['input'] = $input;

            Doc::addJs(\view('form-tool::form.scripts.'.$scriptFilename, $data), 'depend-'.$key);
        }
    }

    public function getChildOptions($values)
    {
        foreach ($this->depend as $depend) {
            $depend->value = $values[$depend->field] ?? null;
        }

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
        return $this->createNiceValue($value);
    }

    protected function createNiceValue($value, $isFull = false)
    {
        if ($value === null && $this->optionType != InputType::CHECKBOX) {
            return null;
        }

        // I don't know why I have ignored the trash
        // $this->withoutTrash(false);

        $this->createOptions(true);

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

                if (! $isFull && $i++ >= 2) {
                    break;
                }
            }

            if ($isFull) {
                return implode(', ', $values);
            }

            return implode(', ', $values).(\count($rawValues) > 3 ? '...' : '');
        } else {
            if ($this->optionType == InputType::CHECKBOX && $this->singleOptions) {
                return $value == $this->valueYes ? $this->captionYes : $this->captionNo;
            }

            if (! is_array($value) && isset($this->options->{$value})) {
                return $this->options->{$value};
            }

            // Check if we have some first value and if that matches with the current value
            return $this->isFirstOption && $this->firstOption && $value == $this->firstOption->value ?
                $this->firstOption->text : null;
        }
    }

    public function getImportValue($value)
    {
        $this->createOptions();

        $value = strtolower(trim($value));
        if ($this->isMultiple) {
            $value = array_map('trim', explode(', ', $value));
        }

        $this->options = array_map('strtolower', (array) $this->options);

        $ids = [];
        if ($this->isMultiple) {
            foreach ($value as $val) {
                foreach ($this->options as $id => $text) {
                    if ($val == $text) {
                        $ids[] = $id;
                    }
                }
            }
        } else {
            foreach ($this->options as $id => $text) {
                if ($value == $text) {
                    return $id;
                }
            }
        }

        return $ids ?: null;
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        $oldValue = $this->createNiceValue($oldValue, true);
        $newValue = $this->createNiceValue($this->value, true);

        $type = 'text'.($this->isMultiple ? ':multiple' : '');

        if ($action == 'update') {
            if ($oldValue != $newValue) {
                return [
                    'type' => $type,
                    'data' => [$oldValue ?? '', $newValue ?? ''],
                ];
            }

            return '';
        }

        return $newValue !== null ? ['type' => $type, 'data' => $newValue] : '';
    }

    public function getOptionData()
    {
        return $this->optionData;
    }

    public function reset()
    {
        $this->options = null;
    }

    public function getOptionsAsArray()
    {
        $this->createOptions();

        return (array) $this->options;
    }

    // This is mainly needed to send as a response to Mobile API
    public function getOptionsAsValueText()
    {
        $this->createOptions();

        $options = (array) $this->options;

        $convert = [];
        if ($this->isFirstOption) {
            if ($this->firstOption === null) {
                $convert[] = [
                    'value' => '',
                    'text' => '(select '.\strtolower($this->label).')',
                ];
            } else {
                $convert[] = [
                    'value' => $this->firstOption->value,
                    'text' => $this->firstOption->text,
                ];
            }
        }

        foreach ($options as $key => $value) {
            $convert[] = [
                'value' => $key,
                'text' => $value,
            ];
        }

        return $convert;
    }
}
