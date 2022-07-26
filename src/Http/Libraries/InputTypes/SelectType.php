<?php

namespace Biswadeep\FormTool\Http\Libraries\InputTypes;

use Illuminate\Support\Facades\DB;

class SelectType extends BaseInputType
{
    public int $type = InputType::Select;
    public string $typeInString = 'select';

    private $options = [];
    private $result = null;
    private $firstOption = '';

    private string $dbTable = '';
    private string $dbTableValue = '';
    private string $dbTableTitle = '';

    // Setter
    public function options($options)
    {
        if (is_string($options))
        {
            $db = explode('.', $options);

            if (count($db) >= 3) {
                $this->dbTable = \trim($db[0]);
                $this->dbTableValue = \trim($db[1]);
                $this->dbTableTitle = \trim($db[2]);
            }
            else {
                throw new \Exception('Wrong format! It should be table_name.value_column.text_column');
            }

            $this->result = DB::table($this->dbTable)->orderBy($this->dbTableValue)->get();
        }
        else if (is_array($options)) {
            $this->options = $options;
        }

        return $this;
    }

    public function noFirst()
    {
        $this->firstOption = null;

        return $this;
    }

    public function first($firstOption)
    {
        $this->firstOption = \trim($firstOption);

        return $this;
    }

    public function getTableValue()
    {
        if (isset($this->options[$this->value]))
            return $this->options[$this->value];

        foreach ($this->result as $row) {
            if ($row->{$this->dbTableValue} === $this->value)
                return $row->{$this->dbTableTitle};
        }

        return null;
    }

    private function getCommonHTML($value)
    {
        $input = '';

        if ($this->firstOption !== null) {
            if ($this->firstOption)
                $input .= '<option value="">' . $this->firstOption .'</option>';
            else
                $input .= '<option value="">(select ' . strtolower($this->label) .')</option>';
        }

        if ($this->result) {
            foreach ($this->result as $row) {
                $input .= '<option value="'. $row->{$this->dbTableValue} .'" '. ($row->{$this->dbTableValue} == $value ? 'selected' : '') .'>' . $row->{$this->dbTableTitle} .'</option>';
            }
        }
        else {
            foreach ($this->options as $val => $text) {
                $input .= '<option value="'. $val .'" '. ($val === $value ? 'selected' : '') .'>' . $text .'</option>';
            }
        }

        $input .= '</select>';

        return $input;
    }

    public function getHTML()
    {
        $value = old($this->dbField, $this->value);

        $input = '<select class="'. implode(' ', $this->classes) .'" id="'. $this->dbField .'" name="'. $this->dbField .'" '. ($this->isRequired ? 'required' : '') .' '. $this->raw  .' '. $this->inlineCSS  .'>';
        
        return $this->htmlParentDiv($input . $this->getCommonHTML($value));
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key . '.' . $this->dbField);

        $value = $value[$index] ?? $this->value;

        $input = '<select class="'. implode(' ', $this->classes) .' input-sm" name="'. $key . '[' . $this->dbField .'][]" '. ($this->isRequired ? 'required' : '') .' '. $this->raw  .' '. $this->inlineCSS  .'>';
        
        return $input . $this->getCommonHTML($value);
    }
}