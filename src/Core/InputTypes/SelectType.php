<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\Options;
use Biswadeep\FormTool\Core\Crud;
use Illuminate\Support\Facades\DB;

class SelectType extends BaseInputType
{
    use Options;

    public int $type = InputType::Select;
    public string $typeInString = 'select';

    //protected $options = [];
    //protected $result = null;
    protected bool $isFirstOption = true;
    protected string $firstOption = '';

    protected $plugins = ['default', 'chosen'];
    protected string $currentPlugin = '';

    //region Setter
    public function noFirst()
    {
        $this->isFirstOption = false;

        return $this;
    }

    public function first($firstOption)
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
        $this->currentPlugin = 'chosen';

        return $this;
    }

    public function plugin($plugin = 'default')
    {
        if (! \in_array($plugin, $this->plugins)) {
            throw new \Exception('Plugin not found: '.$plugin);
        }

        $this->currentPlugin = $plugin;

        return $this;
    }
    //endregion

    public function setPlugin($isMultiple = false)
    {
        if ($this->currentPlugin != 'chosen') {
            return;
        }

        Crud::addCssLink('assets/form-tool/plugins/chosen_v1.8.7/chosen.min.css');
        Crud::addJsLink('assets/form-tool/plugins/chosen_v1.8.7/chosen.jquery.min.js');
        $this->addClass('chosen');

        $config = [
            'width' => '100%',
            'disable_search_threshold' => 10
        ];

        if (! $this->isRequired) {
            $config['allow_single_deselect'] = true;
        }

        if ($this->limitMax) {
            $config['max_selected_options'] = $this->limitMax;
        }

        $this->isFirstOption = true;
        $this->firstOption = '';

        Crud::addJs('$(".chosen").chosen('. \json_encode($config) .');', 'chosen');

        /* TODO:
        if ($isMultiple) {
            Crud::addJs('$(".chosen").trigger("chosen:updated");', 'chosen-update');
        }*/
    }

    private function getCommonHTML($value)
    {
        $this->createOptions();

        $input = '';

        if ($this->isFirstOption) {
            if (! $this->firstOption) {
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

        $input .= '</select>';

        return $input;
    }

    public function getHTML()
    {
        $this->setPlugin();

        $value = old($this->dbField);
        if (! $value) {
            $value = $this->value;
            if ($this->isMultiple) {
                $value = (array)\json_decode($this->value, true);
            }
        }

        $input = '<select class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.($this->isMultiple ? '[]' : '').'" '.$this->raw.$this->inlineCSS.'>';

        return $this->htmlParentDiv($input.$this->getCommonHTML($value));
    }

    public function getHTMLMultiple($key, $index)
    {
        $this->setPlugin(true);

        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $input = '<select class="'.\implode(' ', $this->classes).' input-sm" name="'.$key.'['.$this->dbField.'][]" '.$this->raw.$this->inlineCSS.'>';

        return $input.$this->getCommonHTML($value);
    }
}
