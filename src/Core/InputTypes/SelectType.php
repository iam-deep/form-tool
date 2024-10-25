<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\Guard;
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

    private $quickAddClass = null;

    public function __construct()
    {
        parent::__construct();

        $this->isRemoveTrash = \config('isSoftDelete', true);
    }

    // region Options
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

    public function quickAdd($controllerClass)
    {
        if (! $this->optionData) {
            throw new \Exception('Set options first!');
        }

        foreach ($this->optionData as $optionData) {
            foreach ($optionData as $type => $options) {
                if ('db' != $type) {
                    throw new \Exception('Options must be set to database to use Auto Quick Add!');
                }
            }
        }

        $this->quickAddClass = $controllerClass;

        return $this;
    }

    // endregion

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

    public function getOptions($value)
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

        $this->setPlugin();
        $this->addScript();

        $data['input'] = (object) [
            'type' => 'single',
            'column' => $this->dbField,
            'rawValue' => $this->value,
            'value' => $this->value,
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
            'isMultiple' => $this->isMultiple,
            'options' => $this->getOptions($value),
            'isPlugin' => $this->currentPlugin ? true : false,
            'plugin' => $this->currentPlugin,
            'isQuickAdd' => false,
        ];

        $quickClass = $this->quickAddClass ? new $this->quickAddClass() : null;
        if ($quickClass && Guard::hasCreate($quickClass->route)) {
            $data['input']->isQuickAdd = true;

            $data['input']->quickData = (object) [
                'title' => $quickClass->singularTitle,
                'optionData' => $this->bluePrint->getForm()->getResource()->route.'.'.$this->dbField,
                'route' => config('form-tool.adminURL').'/'.$quickClass->route.'/create?quickAdd=1',
            ];
        }

        return $this->htmlParentDiv(view('form-tool::form.input_types.select', $data)->render());
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $this->setPlugin(true);
        $this->addScript();

        $value = $oldValue ?? $this->value;
        if ($this->isMultiple && is_string($value)) {
            $value = (array) \json_decode($this->value, true);
        }

        // This is needed for depend value
        $this->value = $value;

        $id = $key.'-'.$this->dbField.'-'.$index;
        $name = $key.'['.$index.']['.$this->dbField.']';

        $data['input'] = (object) [
            'type' => 'multiple',
            'key' => $key,
            'index' => $index,
            'column' => $this->dbField,
            'value' => $this->value,
            'oldValue' => $oldValue,
            'id' => $id,
            'name' => $name,
            'classes' => \implode(' ', $this->classes).' '.$key.'-'.$this->dbField,
            'raw' => $this->raw.$this->inlineCSS,
            'isMultiple' => $this->isMultiple,
            'options' => $this->getOptions($value),
        ];

        return \view('form-tool::form.input_types.select', $data)->render();

        // return $input;
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

        $this->setPlugin();
        $this->addScript();

        $data['input'] = (object) [
            'type' => 'single',
            'column' => $this->dbField,
            'rawValue' => $this->value,
            'value' => $this->value,
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
            'isMultiple' => $this->isMultiple,
            'options' => $this->getOptions($this->value),
            'isPlugin' => $this->currentPlugin ? true : false,
            'plugin' => $this->currentPlugin,
            'isQuickAdd' => false,
        ];

        return $this->htmlParentDivFilter(view('form-tool::form.input_types.select', $data)->render());
    }

    /**
     * This method is called by Options trait.
     */
    protected function getDependOptions()
    {
        return $this->getOptions($this->value);
    }
}
