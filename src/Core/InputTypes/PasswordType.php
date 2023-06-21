<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Support\Facades\Hash;

class PasswordType extends BaseInputType
{
    public int $type = InputType::PASSWORD;
    public string $typeInString = 'password';

    public function getNiceValue($value)
    {
        if ($this->value !== null) {
            return '*****';
        }

        return '';
    }

    public function getExportValue($value)
    {
        return '';
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        if ($action == 'update') {
            if ($oldValue && $this->value) {
                return [
                    'type' => $this->typeInString,
                    'data' => [
                        $this->getNiceValue($oldValue) ?: '',
                        $this->getNiceValue($this->value) ?: '',
                    ],
                ];
            }

            if (! $oldValue && $this->value) {
                return [
                    'type' => $this->typeInString,
                    'data' => [
                        '',
                        $this->getNiceValue($this->value) ?: '',
                    ],
                ];
            }

            return '';
        }

        return $this->value !== null ? ['type' => $this->typeInString,
            'data' => $this->getNiceValue($this->value), ] : '';
    }

    public function beforeStore(object $newData)
    {
        if ($newData->{$this->dbField} == '') {
            return null;
        }

        return Hash::make($newData->{$this->dbField});
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        // If user doesn't provide any password then let's put the previous password
        if ($newData->{$this->dbField} == '') {
            return $oldData->{$this->dbField};
        }

        return Hash::make($newData->{$this->dbField});
    }

    public function getHTML()
    {
        $data['input'] = (object) [
            'type' => 'single',
            'column' => $this->dbField,
            'rawValue' => $this->value,
            'value' => $this->value,
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
        ];

        return $this->htmlParentDiv(\view('form-tool::form.input_types.password', $data)->render());
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
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
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
        ];

        return \view('form-tool::form.input_types.password', $data)->render();
    }
}
