<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Support\FileManager;

class FileType extends BaseInputType
{
    public int $type = InputType::File;
    public string $typeInString = 'file';

    private string $path = '';
    private float $maxSizeInKb = 0;
    private $accept = '';

    public function __construct()
    {
        $this->classes = [];
    }

    // Setter
    public function required($isRequired = true): FileType
    {
        $this->isRequired = $isRequired;

        if ($isRequired) {
            $this->validations['required'] = 'required';
        } else {
            if (isset($this->validations['required'])) {
                unset($this->validations['required']);
            }
        }

        return $this;
    }

    public function path(string $path)
    {
        $this->path = \trim($path);

        return $this;
    }

    public function maxSize(float $maxSizeInKb)
    {
        $this->maxSizeInKb = $maxSizeInKb;

        return $this;
    }

    public function accept($accept)
    {
        $this->accept = \trim($accept);
    }

    public function getValidations($type)
    {
        $request = request();

        $validations = [];

        if ('store' == $type) {
            if ($this->isRequired) {
                $validations[] = 'required';
            } else {
                $validations[] = 'nullable';
            }
        } else {
            if ($this->isRequired && ! $request->get($this->dbField)) {
                $validations[] = 'required';
            } else {
                $validations[] = 'nullable';
            }
        }

        if ($request->file($this->dbField)) {
            $validations[] = 'file';

            $allowedTypes = FileManager::getAllowedTypes();
            if ($allowedTypes) {
                $validations['mimes'] = 'mimes:'.$allowedTypes;
            }
        }

        if ($this->maxSizeInKb > 0) {
            $validations[] = 'max:'.$this->maxSizeInKb;
        }

        return $validations;
    }

    public function beforeStore(object $newData)
    {
        $request = request();

        if ($this->isArray) {
            $file = $request->file($this->parentField);
            $file = $file[$this->index][$this->dbField] ?? null;

            return FileManager::uploadFile($file, $this->path);
        } else {
            $file = $request->file($this->dbField);

            return FileManager::uploadFile($file, $this->path);
        }
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $request = request();

        if ($this->isArray) {
            $oldFile = $request->get($this->parentField);
            $oldFile = $oldFile[$this->index][$this->dbField] ?? null;

            $file = $request->file($this->parentField);
            $file = $file[$this->index][$this->dbField] ?? null;

            $filename = FileManager::uploadFile($file, $this->path, $oldFile);
            if ($filename !== null) {
                return $filename;
            }
        } else {
            $oldFile = $request->post($this->dbField);
            $file = $request->file($this->dbField);

            $filename = FileManager::uploadFile($file, $this->path, $oldFile);
            if ($filename !== null) {
                return $filename;
            }
        }

        // No files have been uploaded let's return the old file if we have
        return $oldFile ?? null;
    }

    public function afterUpdate(object $oldData, object $newData)
    {
        $oldFile = $oldData->{$this->dbField} ?? null;
        $newFile = $newData->{$this->dbField} ?? null;

        if ($oldFile != $newFile) {
            FileManager::deleteFile($oldFile);
        }
    }

    public function afterDestroy(object $oldData)
    {
        FileManager::deleteFile($oldData->{$this->dbField} ?? null);
    }

    public function getTableValue()
    {
        if ($this->value) {
            return '<a href="'.asset($this->value).'" target="_blank"><i class="fa '.FileManager::getFileIcon($this->value).' fa-2x"></i></a>';
        }

        return null;
    }

    public function getHTML()
    {
        $value = old($this->dbField, $this->value);

        $groupId = 'group-'.$this->dbField;

        if ($this->isRequired && ! $value) {
            $this->raw('required');
        }

        $input = '<div class="row">
            <div class="col-sm-3">
                <input type="file" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" accept="'.$this->accept.'" '.$this->raw.$this->inlineCSS.' />
            </div>';

        if ($this->value) {
            $script = '$(\'#'.$groupId.'\').remove();';
            if ($this->isRequired) {
                $script .= '$(\'#'.$this->dbField.'\').prop(\'required\', \'required\')';
            }

            if (FileManager::isImage($this->value)) {
                $file = '<img src="'.asset($value).'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
            } else {
                $file = '<i class="fa '.FileManager::getFileIcon($this->value).' fa-5x"></i>';
            }

            $input .= '<div class="col-sm-6" id="'.$groupId.'"> &nbsp; 
                <a href="'.asset($value).'" target="_blank">'.$file.'</a>
                <input type="hidden" name="'.$this->dbField.'" value="'.$this->value.'">
                <button class="close pull-left" aria-hidden="true" type="button" onclick="'.$script.'"><i class="fa fa-times"></i></button>
            </div>';
        }

        $input .= '</div>';

        return $this->htmlParentDiv($input);
    }

    public function getHTMLMultiple($key, $index)
    {
        $value = old($key.'.'.$this->dbField);
        $value = $value[$index] ?? $this->value;

        $groupId = $key.'-group-'.$this->dbField.'-'.$index;
        $inputId = $key.'-'.$this->dbField.'-'.$index;
        $name = $key.'['.$index.']['.$this->dbField.']';

        if ($this->isRequired && ! $value) {
            $this->raw('required');
        }

        $input = '<div class="row">
            <div class="col-sm-3">
                <input type="file" class="'.\implode(' ', $this->classes).'" id="'.$inputId.'" name="'.$name.'" accept="'.$this->accept.'" '.$this->raw.$this->inlineCSS.' />
            </div>';

        if ($value) {
            $script = '$(\'#'.$groupId.'\').remove();';
            if ($this->isRequired) {
                $script .= '$(\'#'.$inputId.'\').prop(\'required\', \'required\')';
            }

            if (FileManager::isImage($value)) {
                $file = '<img src="'.asset($value).'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
            } else {
                $file = '<i class="fa '.FileManager::getFileIcon($value).' fa-5x"></i>';
            }

            $input .= '<div class="col-sm-6" id="'.$groupId.'"> &nbsp; 
                <a href="'.asset($value).'" target="_blank">'.$file.'</a>
                <input type="hidden" name="'.$name.'" value="'.$value.'">
                <button class="close pull-right" aria-hidden="true" type="button" onclick="'.$script.'"><i class="fa fa-times"></i></button>
            </div>';
        }

        $input .= '</div>';

        return $input;
    }
}
