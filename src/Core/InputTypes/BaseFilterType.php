<?php

namespace Biswadeep\FormTool\Core\InputTypes;

class BaseFilterType extends BaseInputType
{
    protected $filterOptions = [];

    public function setFilterOptions($options)
    {
        $this->filterOptions = $options;
    }

    public function getFilterOptions()
    {
        return $this->filterOptions;
    }

    public function applyFilter($query, $operator = '=')
    {
        if ($this->value !== null) {
            $query->where($this->getAlias().'.'.$this->dbField, $operator, $this->value);
        }
    }

    public function getFilterHTML()
    {
        return '<p>Not implemented!</p>';
    }

    public function htmlParentDivFilter($input)
    {
        return '<div class="'.config('form-tool.styleClass.filter-form-group').'">
            <label for="'.$this->dbField.'" class="'.config('form-tool.styleClass.filter-label').'">'.
                $this->label.'</label><br />
            '.$input.'
        </div>';
    }
}
