<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\BaseFilterType;
use Biswadeep\FormTool\Core\InputTypes\BaseInputType;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Support\Facades\DB;

//use Biswadeep\FormTool\Core\BluePrint;

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

    public function __construct($fields = [])
    {
        $this->fieldsToFilter = $fields;
    }

    public function setBluePrint(BluePrint $bluePrint)
    {
        $this->bluePrint = $bluePrint;
    }

    public function create()
    {
        if (! $this->fieldsToFilter) {
            $this->setDefaultFilter();
        }

        $request = request();

        $html = [];
        foreach ($this->fieldsToFilter as $key => $option) {
            if (\is_integer($key)) {
                $field = $this->bluePrint->getInputTypeByDbField($option);
                if ($field instanceof BaseFilterType) {
                    $field->setValue($request->query($field->getDbField()));

                    $html[] = $field->getFilterHTML();
                } elseif ($field) {
                    throw new \Exception('"'.$option.'" is not a Filter Type.');
                } else {
                    throw new \Exception('"'.$option.'" not found in the BluePrint.');
                }
            } else {
                $field = $this->bluePrint->getInputTypeByDbField($key);
                if ($field instanceof BaseFilterType) {
                    if (! in_array($option, $field->getFilterOptions())) {
                        throw new \Exception('"'.$option.'" option is not available for this field type.');
                    }

                    $label = $field->getLabel();
                    $dbField = $field->getDbField();

                    $fromField = clone $field;
                    $toField = clone $field;

                    $fromField->setValue($request->query($dbField.'From'));
                    $toField->setValue($request->query($dbField.'To'));

                    $html[] = $fromField->setDbField($dbField.'From')->label($label.' From')->getFilterHTML();
                    $html[] = $toField->setDbField($dbField.'To')->label($label.' To')->getFilterHTML();

                    $this->dateRangeFields[$dbField]['From'] = $fromField;
                    $this->dateRangeFields[$dbField]['To'] = $toField;
                } elseif ($option instanceof BaseFilterType) {
                    if (! $option->getDbField()) {
                        $option->setDbField($key);
                    }

                    $option->setValue($request->query($option->getDbField()));

                    $html[] = $option->getFilterHTML();
                } elseif ($field || $option instanceof BaseInputType) {
                    throw new \Exception('"'.$key.'" is not a Filter Type.');
                } else {
                    throw new \Exception('"'.$key.'" not found in the BluePrint.');
                }
            }
        }

        $html[] = '<button class="btn btn-primary btn-sm btn-flat" href="'.url($this->bluePrint->form->getUrl()).'" style="margin-top:25px;">Filter</button>';

        $queries = $request->except('page');
        if ($queries) {
            $html[] = '<a class="btn btn-default btn-sm btn-flat" href="'.url($this->bluePrint->form->getUrl()).'" style="margin-top:25px;"><i class="fa fa-times"></i> Clear All</a>';
        }

        return $html;
    }

    public function apply()
    {
        $where = function ($query) {
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

        return $where;
    }

    private function setDefaultFilter()
    {
        foreach ($this->bluePrint->getList() as $field) {
            if ($field instanceof BaseFilterType) {
                $this->fieldsToFilter[] = $field->getDbField();
            }
        }
    }
}
