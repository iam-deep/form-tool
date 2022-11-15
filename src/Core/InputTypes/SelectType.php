<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\Doc;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Core\InputTypes\Common\Options;
use Illuminate\Support\Facades\DB;

class SelectType extends BaseFilterType
{
    use Options;

    public int $type = InputType::Select;
    public string $typeInString = 'select';

    //protected $options = [];
    //protected $result = null;
    protected bool $isFirstOption = true;
    protected $firstOption = null;

    protected $plugins = ['default', 'chosen'];
    protected string $currentPlugin = '';

    //region Setter
    public function noFirst()
    {
        $this->isFirstOption = false;

        return $this;
    }

    public function first(string $firstOption)
    {
        $this->isFirstOption = true;
        $this->firstOption = \trim($firstOption);

        return $this;
    }

    // This only works for chosen
    public function placeholder(string $placeholder): SelectType
    {
        $this->raw('data-placeholder="'.$placeholder.'"');

        return $this;
    }

    public function multiple()
    {
        $this->isMultiple = true;
        $this->raw('multiple');
        $this->plugin('chosen');

        return $this;
    }

    public function plugin($plugin = 'default')
    {
        if (! \in_array($plugin, $this->plugins)) {
            throw new \Exception('Plugin not found: '.$plugin);
        }

        $this->currentPlugin = $plugin;
        $this->isFirstOption = true;
        $this->firstOption = '';

        return $this;
    }
    //endregion

    public function beforeStore($newData)
    {
        return $this->getFormValue($newData);
    }

    public function beforeUpdate($oldData, $newData)
    {
        return $this->getFormValue($newData);
    }

    private function getFormValue($newData)
    {
        $val = $newData->{$this->dbField};
        if ($this->isMultiple) {
            if ($val === null) {
                return null;
            }

            // If we have multiple options then let's keep it in json
            return \json_encode($val);
        }

        return $val;
    }

    public function setPlugin($isMultiple = false)
    {
        if ($this->currentPlugin != 'chosen') {
            return;
        }

        Doc::addCssLink('assets/form-tool/plugins/chosen_v1.8.7/chosen.min.css');
        Doc::addJsLink('assets/form-tool/plugins/chosen_v1.8.7/chosen.jquery.min.js');
        $this->addClass('chosen');

        $config = [
            'width' => '100%',
            'disable_search_threshold' => 10,
        ];

        if (! $this->isRequired) {
            $config['allow_single_deselect'] = true;
        }

        if ($this->limitMax) {
            $config['max_selected_options'] = $this->limitMax;
        }

        $this->removeRaw('required');

        Doc::addJs('$(".chosen").chosen('.\json_encode($config).');', 'chosen');

        /* TODO:
        if ($isMultiple) {
            Doc::addJs('$(".chosen").trigger("chosen:updated");', 'chosen-update');
        }*/
    }

    private function getOptions($value)
    {
        $this->createOptions();

        $input = '';

        if ($this->isFirstOption) {
            if ($this->firstOption === null) {
                $input .= '<option value="">(select '.\strtolower($this->label).')</option>';
            } else {
                $input .= '<option value="">'.$this->firstOption.'</option>';
            }
        }

        if ($this->isMultiple) {
            foreach ($this->options as $val => $text) {
                $input .= '<option value="'.$val.'" '.(\is_array($value) && \in_array($val, $value) ? 'selected' : '').'>'.$text.'</option>';
            }
        } else {
            foreach ($this->options as $val => $text) {
                $input .= '<option value="'.$val.'" '.($val == $value ? 'selected' : '').'>'.$text.'</option>';
            }
        }

        return $input;
    }

    public function getHTML()
    {
        $value = old($this->dbField);
        if ($value === null) {
            $value = $this->value;
            if ($this->isMultiple) {
                $value = (array) \json_decode($this->value, true);
            }
        }

        return $this->htmlParentDiv($this->getInput($value));
    }

    public function getHTMLMultiple($key, $index)
    {
        $this->setPlugin(true);

        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<select class="'.\implode(' ', $this->classes).' input-sm" id="'.$key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']" '.$this->raw.$this->inlineCSS.'>';
        $input .= $this->getOptions($value);
        $input .= '</select>';

        return $input;
    }

    public function applyFilter($query, $operator = '=')
    {
        if ($this->isMultiple) {
            if ($this->value === null || ! is_array($this->value)) {
                return;
            }

            foreach ($this->value as $value) {
                $raw = \sprintf("JSON_SEARCH(%s, 'one', '%s')", $this->dbField, $value);
                $query->whereNotNull(DB::raw($raw));
            }
        } else {
            parent::applyFilter($query, $operator);
        }
    }

    public function getFilterHTML()
    {
        $this->raw('onChange="form.submit()"');

        return $this->htmlParentDivFilter($this->getInput($this->value));
    }

    private function getInput($value)
    {
        $this->setPlugin();

        $input = '<select class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.($this->isMultiple ? '[]' : '').'" '.$this->raw.$this->inlineCSS.'>';
        $input .= $this->getOptions($value);
        $input .= '</select>';

        return $input;
    }
}
