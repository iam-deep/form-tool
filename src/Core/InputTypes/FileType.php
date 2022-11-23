<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Biswadeep\FormTool\Support\FileManager;
use Biswadeep\FormTool\Support\ImageCache;

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

        $validations = parent::getValidations($type);

        if ($this->maxSizeInKb > 0 && ! isset($validations['max'])) {
            $validations['max'] = 'max:'.$this->maxSizeInKb;
        }

        if ($request->file($this->dbField)) {
            $validations[] = 'file';

            $allowedTypes = FileManager::getAllowedTypes();
            if ($allowedTypes && ! isset($validations['mimes'])) {
                $validations['mimes'] = 'mimes:'.$allowedTypes;
            }
        } else {
            $validations = [];
        }

        if ('store' == $type) {
            if ($this->isRequired) {
                $validations['required'] = 'required';
            } else {
                $validations['required'] = 'nullable';
            }
        } else {
            if ($this->isRequired && ! $request->get($this->dbField)) {
                $validations['required'] = 'required';
            } else {
                $validations['required'] = 'nullable';
            }
        }

        return $validations;
    }

    public function beforeStore(object $newData)
    {
        $request = request();

        if ($this->isArray) {
            $file = $request->file($this->parentField);
            $file = $file[$this->index][$this->dbField] ?? null;

            $this->value = FileManager::uploadFile($file, $this->path);

            return $this->value;
        } else {
            $file = $request->file($this->dbField);
            $this->value = FileManager::uploadFile($file, $this->path);

            return $this->value;
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
                $this->value = $filename;

                return $this->value;
            }
        } else {
            $oldFile = $request->post($this->dbField);
            $file = $request->file($this->dbField);

            $filename = FileManager::uploadFile($file, $this->path, $oldFile);
            if ($filename !== null) {
                $this->value = $filename;

                return $this->value;
            }
        }

        // No files have been uploaded let's return the old file if we have
        $this->value = $oldFile ?? null;

        return $this->value;
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

    public function getNiceValue($value)
    {
        if ($value) {
            return '<a href="'.asset($value).'" target="_blank"><i class="fa '.FileManager::getFileIcon($value).' fa-2x"></i></a>';
        }

        return null;
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        $newValue = $this->value;

        if ($action == 'update') {
            if ($oldValue != $newValue) {
                return [
                    'type' => $this->typeInString,
                    'data' => [$oldValue ?: '', $newValue ?: ''],
                ];
            }

            return '';
        }

        return $newValue ? ['type' => $this->typeInString, 'data' => $newValue] : '';
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
                $image = ImageCache::resize($this->value);
                $file = '<img src="'.asset($image).'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
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

        if ($this->isRequired) {
            if (! $value) {
                $this->raw('required');
            } else {
                $this->removeRaw('required');
            }
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
                $image = ImageCache::resize($value);
                $file = '<img src="'.asset($image).'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
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
