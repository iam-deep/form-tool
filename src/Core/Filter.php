<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Core\InputTypes\BaseFilterType;
use Deep\FormTool\Core\InputTypes\BaseInputType;

// Select, Checkbox
// Single, Multiple, Manual & Db
// Date (From, To, Range)
// Time (From, To, Range)
// Multiple (Same Input)

class Filter
{
    protected BluePrint $bluePrint;
    protected $fieldsToFilter = [];

    protected $dateRangeFields = [];

    private $isInitialized = false;

    public function __construct($fields = [])
    {
        $this->fieldsToFilter = $fields;

        if (! $fields) {
            $this->setDefaultFilter();
        }
    }

    private function initialize()
    {
        if ($this->isInitialized) {
            return;
        }

        $this->isInitialized = true;

        $this->bluePrint = clone $this->bluePrint;
    }

    public function setBluePrint(BluePrint $bluePrint)
    {
        $this->bluePrint = $bluePrint;
    }

    public function create()
    {
        $this->initialize();

        $request = request();

        $data = new \stdClass();

        $data->inputs = [];
        foreach ($this->fieldsToFilter as $key => $option) {
            if (\is_integer($key)) {
                $field = $this->bluePrint->getInputTypeByDbField($option);
                if ($field instanceof BaseFilterType) {
                    $field->required(false);
                    $field->setValue($request->query($field->getDbField()));

                    $data->inputs[] = $field->getFilterHTML();
                } elseif ($field) {
                    throw new \InvalidArgumentException('"'.$option.'" is not a Filter Type.');
                } else {
                    throw new \InvalidArgumentException('"'.$option.'" not found in the BluePrint.');
                }
            } else {
                $field = $this->bluePrint->getInputTypeByDbField($key);
                if ($field instanceof BaseFilterType) {
                    if (! in_array($option, $field->getFilterOptions())) {
                        throw new \InvalidArgumentException(\sprintf(
                            '"%s" option is not available for this field type.',
                            $option
                        ));
                    }

                    $field->required(false);
                    $label = $field->getLabel();
                    $dbField = $field->getDbField();

                    $fromField = clone $field;
                    $toField = clone $field;

                    $fromField->setValue($request->query($dbField.'From'));
                    $toField->setValue($request->query($dbField.'To'));

                    $data->inputs[] = $fromField->setDbField($dbField.'From')->label($label.' From')->getFilterHTML();
                    $data->inputs[] = $toField->setDbField($dbField.'To')->label($label.' To')->getFilterHTML();

                    $this->dateRangeFields[$dbField]['From'] = $fromField;
                    $this->dateRangeFields[$dbField]['To'] = $toField;
                } elseif ($option instanceof BaseFilterType) {
                    if (! $option->getDbField()) {
                        $option->setDbField($key);
                    }

                    $field->required(false);
                    $option->setValue($request->query($option->getDbField()));

                    $data->inputs[] = $option->getFilterHTML();
                } elseif ($field || $option instanceof BaseInputType) {
                    throw new \InvalidArgumentException('"'.$key.'" is not a Filter Type.');
                } else {
                    throw new \InvalidArgumentException('"'.$key.'" not found in the BluePrint.');
                }
            }
        }
        unset($option);

        $queries = array_filter($request->except('page'));
        $data->showClearButton = ! empty($queries);
        $data->clearUrl = url($this->bluePrint->getForm()->getUrl());

        return $data;
    }

    public function apply()
    {
        $this->initialize();

        return function ($query) {
            $request = request();
            foreach ($this->fieldsToFilter as $key => $option) {
                if (\is_integer($key)) {
                    $field = $this->bluePrint->getInputTypeByDbField($option);
                    if ($field instanceof BaseFilterType) {
                        $val = $request->query($field->getDbField());
                        $field->setValue($val);

                        $field->applyFilter($query);
                    }
                } else {
                    if (isset($this->dateRangeFields[$key]) && $option == 'range') {
                        $this->dateRangeFields[$key]['From']->setDbField($key)->applyFilter($query, '>=');
                        $this->dateRangeFields[$key]['To']->setDbField($key)->applyFilter($query, '<=');
                    } elseif ($option instanceof BaseFilterType) {
                        $val = $request->query($option->getDbField());
                        $option->setValue($val);

                        $option->applyFilter($query);
                    }
                }
            }
        };
    }

    private function setDefaultFilter()
    {
        foreach ($this->bluePrint->getInputList() as $field) {
            if ($field instanceof BaseFilterType) {
                $this->fieldsToFilter[] = $field->getDbField();
            }
        }
    }
}
