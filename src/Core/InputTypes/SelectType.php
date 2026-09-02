<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\Guard;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISaveable;
use Deep\FormTool\Core\InputTypes\Common\IVisibilityController;
use Deep\FormTool\Core\InputTypes\Common\Options;
use Deep\FormTool\Core\InputTypes\Common\Saveable;
use Deep\FormTool\Core\InputTypes\Common\VisibilityRules;
use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Facades\DB;

class SelectType extends BaseFilterType implements ISaveable, IVisibilityController
{
    use Options;
    use Saveable;
    use VisibilityRules;

    public int $type = InputType::SELECT;
    public string $typeInString = 'select';

    protected bool $isFirstOption = true;
    protected $firstOption = null;

    protected $plugins = ['default', 'chosen', 'virtual'];
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
        if (! $this->currentPlugin) {
            $this->plugin('virtual');
        }

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
            throw new FormToolException('Set options first!');
        }

        foreach ($this->optionData as $optionData) {
            foreach ($optionData as $type => $options) {
                if ('db' != $type) {
                    throw new FormToolException('Options must be set to database to use Auto Quick Add!');
                }
            }
        }

        $this->quickAddClass = $controllerClass;

        return $this;
    }

    public function hide(string|array $fields, mixed $values, array $messages = []): static
    {
        return $this->addVisibilityRule('hide', $fields, $values, $messages);
    }

    public function show(string|array $fields, mixed $values, array $messages = []): static
    {
        return $this->addVisibilityRule('show', $fields, $values, $messages);
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

            $val = $this->normalizeMultipleValue($val);

            // If we have multiple options then let's keep it in json
            return \json_encode($val);
        }

        return $val;
    }

    public function setPlugin($isMultiple = false)
    {
        if ($this->currentPlugin == 'chosen') {
            $this->setChosenDependencies($isMultiple);

            $this->addClass('chosen');
            $this->removeRaw('required');
        } elseif ($this->currentPlugin == 'virtual') {
            $this->setVirtualDependencies($isMultiple);

            $this->removeClass(\config('form-tool.styleClass.input-field'));
            $this->addClass('virtual-select');
            $this->removeRaw('required');
        }
    }

    public function setDependencies($isMultiple = false)
    {
        $this->setChosenDependencies($isMultiple);
    }

    public function setChosenDependencies($isMultiple = false)
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

    public function setVirtualDependencies($isMultiple = false)
    {
        Doc::addCssLink('assets/form-tool/plugins/virtual-select/virtual-select.min.css');
        Doc::addJsLink('assets/form-tool/plugins/virtual-select/virtual-select.min.js');
        Doc::addCss(
            '.virtual-select, .virtual-select .vscomp-wrapper { max-width: 100%; width: 100%; }',
            'virtual-select'
        );

        $script = <<<'JS'
function formToolInitVirtualSelect(selector) {
    if (!window.VirtualSelect) {
        return;
    }

    document.querySelectorAll(selector).forEach(function(field) {
        var selectedValue = field.getValue
            ? field.getValue()
            : JSON.parse(field.dataset.selectedValue || 'null');

        if (field.destroy) {
            field.destroy();
            field.innerHTML = '';
        } else if (field.virtualSelect) {
            field.virtualSelect.destroy();
            field.innerHTML = '';
        }

        VirtualSelect.init({
            ele: field,
            name: field.dataset.name,
            multiple: field.dataset.multiple === '1',
            options: JSON.parse(field.dataset.options || '[]'),
            placeholder: field.dataset.placeholder || '',
            selectedValue: selectedValue,
            search: true,
            maxWidth: '100%',
            setValueAsArray: field.dataset.multiple === '1'
        });
    });
}
formToolInitVirtualSelect('.virtual-select');
JS;

        Doc::addJs($script, 'virtual-select');

        if ($isMultiple) {
            Doc::addJs("formToolInitVirtualSelect('.virtual-select');", 'virtual-select-create', 'multiple_after_add');
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

    public function getVirtualOptions($value)
    {
        $this->createOptions();

        $options = [];

        if ($this->isFirstOption && $this->firstOption !== null
            && ($this->firstOption->value !== '' || $this->firstOption->text !== '')
        ) {
            $options[] = [
                'label' => $this->firstOption->text,
                'value' => (string) $this->firstOption->value,
            ];
        }

        foreach ($this->options as $val => $text) {
            $options[] = [
                'label' => $text,
                'value' => (string) $val,
            ];
        }

        return $options;
    }

    public function getOptionsForFilter($value)
    {
        $this->createOptions();

        $options = (array) $this->options;

        $input = '';

        // We will append the first option if it has options
        if ($options) {
            $input .= '<option value="">(select '.\strtolower($this->label).')</option>';
        }

        if ($this->isFirstOption && $this->firstOption?->value !== null) {
            $options = [$this->firstOption->value => $this->firstOption->text] + $options;
        }

        if ($this->isMultiple) {
            foreach ($options as $val => $text) {
                $input .= '<option value="'.$val.'" '.(\is_array($value) && \in_array($val, $value) ? 'selected' : '').'>'.$text.'</option>';
            }
        } else {
            foreach ($options as $val => $text) {
                $input .= '<option value="'.$val.'" '.($value !== null && $val == $value ? 'selected' : '').'>'.$text.'</option>';
            }
        }

        return $input;
    }

    public function getHTML()
    {
        if ($this->visibilityRules && $this->isMultiple) {
            throw new FormToolException('Visibility rules require a single-value controlling select.');
        }

        $value = old($this->dbField);
        if ($value === null) {
            $value = $this->value;
            if ($this->isMultiple && ! $this->isSaveAt()) {
                $value = $this->normalizeMultipleValue($this->value);
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
            'virtualOptions' => $this->getVirtualOptions($value),
            'virtualValue' => $this->virtualValue($value),
            'virtualPlaceholder' => $this->virtualPlaceholder(),
            'isPlugin' => $this->currentPlugin ? true : false,
            'plugin' => $this->currentPlugin,
            'isQuickAdd' => false,
            'visibilityRules' => $this->visibilityRules
                ? json_encode($this->visibilityRules, JSON_THROW_ON_ERROR)
                : null,
        ];

        $quickClass = $this->quickAddClass ? new $this->quickAddClass() : null;
        if ($quickClass && Guard::hasCreate($quickClass->route)) {
            $data['input']->isQuickAdd = true;

            $data['input']->quickData = (object) [
                'title' => $quickClass->singularTitle,
                'optionData' => $this->bluePrint->getForm()->getResource()->route.'.'.$this->dbField,
                'route' => $quickClass->route.'/create?quickAdd=1',
            ];
        }

        return $this->htmlParentDiv(view('form-tool::form.input_types.select', $data)->render());
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $this->setPlugin(true);
        $this->addScript();

        $value = $oldValue ?? $this->value;
        if ($this->isMultiple) {
            $value = $this->normalizeMultipleValue($value);
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
            'virtualOptions' => $this->getVirtualOptions($value),
            'virtualValue' => $this->virtualValue($value),
            'virtualPlaceholder' => $this->virtualPlaceholder(),
            'visibilityRules' => null,
            'plugin' => $this->currentPlugin,
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

            $column = $this->getAlias().$this->dbField;
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
            'options' => $this->getOptionsForFilter($this->value),
            'virtualOptions' => $this->getVirtualOptions($this->value),
            'virtualValue' => $this->virtualValue($this->value),
            'virtualPlaceholder' => $this->virtualPlaceholder(),
            'isPlugin' => $this->currentPlugin ? true : false,
            'plugin' => $this->currentPlugin,
            'isQuickAdd' => false,
            'visibilityRules' => null,
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

    private function virtualValue($value)
    {
        if ($this->isMultiple) {
            return array_map('strval', (array) $value);
        }

        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }

    private function normalizeMultipleValue($value): array
    {
        if (\is_array($value)) {
            return $value;
        }

        if (! \is_string($value) || $value === '') {
            return (array) $value;
        }

        $decoded = \json_decode($value, true);
        if (! \is_array($decoded)) {
            return \explode(',', $value);
        }

        $legacyDecoded = \json_decode(\implode(',', $decoded), true);

        return \is_array($legacyDecoded) ? $legacyDecoded : $decoded;
    }

    private function virtualPlaceholder(): string
    {
        if ($this->firstOption !== null && $this->firstOption->text !== '') {
            return $this->firstOption->text;
        }

        return '(select '.\strtolower($this->label).')';
    }
}
