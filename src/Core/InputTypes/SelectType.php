<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISaveable;
use Deep\FormTool\Core\InputTypes\Common\Options;
use Deep\FormTool\Core\InputTypes\Common\Saveable;
use Illuminate\Support\Facades\DB;

class SelectType extends BaseFilterType implements ISaveable
{
    use Options;
    use Saveable;

    public int $type = InputType::SELECT;
    public string $typeInString = 'select';

    protected bool $isFirstOption = true;
    protected $firstOption = null;

    protected $plugins = ['default', 'chosen'];
    protected string $currentPlugin = '';

    public function __construct()
    {
        parent::__construct();

        $this->isRemoveTrash = \config('isSoftDelete', true);
    }

    //region Options
    public function noFirst()
    {
        $this->isFirstOption = false;

        return $this;
    }

    public function first(string $firstOption, $firstValue = '')
    {
        $this->isFirstOption = true;

        $this->firstOption = new \stdClass();
        $this->firstOption->text = $firstOption;
        $this->firstOption->value = $firstValue;

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
            throw new \InvalidArgumentException('Plugin not found: '.$plugin);
        }

        $this->currentPlugin = $plugin;

        if ($this->firstOption == null) {
            $this->isFirstOption = true;

            $this->firstOption = new \stdClass();
            $this->firstOption->text = '';
            $this->firstOption->value = '';
        }

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
        $val = $newData->{$this->dbField} ?? null;
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

        $this->setDependencies($isMultiple);

        $this->addClass('chosen');
        $this->removeRaw('required');
    }

    public function setDependencies($isMultiple = false)
    {
        Doc::addCssLink('assets/form-tool/plugins/chosen_v1.8.7/chosen.min.css');
        Doc::addJsLink('assets/form-tool/plugins/chosen_v1.8.7/chosen.jquery.min.js');

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

        Doc::addJs('$(".chosen").chosen('.\json_encode($config).');', 'chosen');

        if ($isMultiple) {
            Doc::addJs('$(".chosen").chosen('.\json_encode($config).');', 'chosen-create', 'multiple_after_add');
        }
    }

    private function getOptions($value)
    {
        $this->createOptions();

        $input = '';

        if ($this->isFirstOption) {
            if ($this->firstOption === null) {
                $input .= '<option value="">(select '.\strtolower($this->label).')</option>';
            } else {
                $input .= '<option value="'.$this->firstOption->value.'">'.$this->firstOption->text.'</option>';
            }
        }

        if ($this->isMultiple) {
            foreach ($this->options as $val => $text) {
                $input .= '<option value="'.$val.'" '.(\is_array($value) && \in_array($val, $value) ? 'selected' : '')
                    .'>'.$text.'</option>';
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
            if ($this->isMultiple && ! $this->isSaveAt()) {
                $value = (array) \json_decode($this->value, true);
            }
        }

        // This is needed for depend value
        $this->value = $value;

        return $this->htmlParentDiv($this->getInput($value));
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $this->setPlugin(true);
        $this->addScript();

        $value = $oldValue ?? $this->value;
        if ($this->isMultiple) {
            $value = (array) \json_decode($this->value, true);
        }

        // This is needed for depend value
        $this->value = $value;
        $input = '<select class="'.\implode(' ', $this->classes).' '.$key.'-'.$this->dbField.' input-sm" id="'.
            $key.'-'.$this->dbField.'-'.$index.'" name="'.$key.'['.$index.']['.$this->dbField.']'.($this->isMultiple ? '[]' : '').'" '.$this->raw.
            $this->inlineCSS.'>';
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

            $column = $this->getAlias().'.'.$this->dbField;
            foreach ($this->value as $value) {
                $raw = \sprintf("JSON_SEARCH(%s, 'one', '%s')", $column, $value);
                $query->whereNotNull(DB::raw($raw));
            }
        } else {
            parent::applyFilter($query, $operator);
        }
    }

    public function getFilterHTML()
    {
        // $this->raw('onChange="form.submit()"');

        return $this->htmlParentDivFilter($this->getInput($this->value));
    }

    /**
     * This method is called by Options trait.
     */
    protected function getDependOptions()
    {
        return $this->getOptions($this->value);
    }

    private function getInput($value)
    {
        $this->setPlugin();
        $this->addScript();

        $input = '<select class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.
            $this->dbField.($this->isMultiple ? '[]' : '').'" '.$this->raw.$this->inlineCSS.'>';
        $input .= $this->getOptions($value);
        $input .= '</select>';

        return $input;
    }
}
