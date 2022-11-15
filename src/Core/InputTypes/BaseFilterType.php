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
        if ($this->value !== null)
            $query->where($this->dbField, $operator, $this->value);
    }

    public function getFilterHTML()
    {
        return '<p>Not implemented!</p>';
    }

    public function htmlParentDivFilter($input)
    {
        return '<div class="form-group">
            <label for="'.$this->dbField.'">'.$this->label.'</label><br />
            '.$input.'
        </div>';
    }
}
