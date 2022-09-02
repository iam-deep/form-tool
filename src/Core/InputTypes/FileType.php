<?php

namespace Biswadeep\FormTool\Core\InputTypes;

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
            if ($this->isRequired && !$request->get($this->dbField)) {
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
        return FileManager::uploadFile($this->dbField, $this->path);
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $oldFile = request()->get($this->dbField);
        $filename = FileManager::uploadFile($this->dbField, $this->path, $oldFile);
        if ($filename !== null) {
            return $filename;
        }

        // No files have been uploaded let's return the old file if have one
        return $oldFile ?? '';
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

        $script = '$(\'#image-group-'.$this->dbField.'\').remove();';
        if ($this->isRequired) {
            $script .= '$(\'#'.$this->dbField.'\').prop(\'required\', \'required\')';
        }

        $input = '<div class="row">
            <div class="col-sm-3">
                <input type="file" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.'" name="'.$this->dbField.'" '.($this->isRequired && !$this->value ? 'required' : '').' accept="'.$this->accept.'" '.$this->raw.' '.$this->inlineCSS.' />
            </div>';

        if ($this->value) {
            if (FileManager::isImage($this->value)) {
                $file = '<img src="'.asset($value).'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
            } else {
                $file = '<i class="fa '.FileManager::getFileIcon($this->value).' fa-5x"></i>';
            }

            $input .= '<div class="col-sm-6" id="image-group-'.$this->dbField.'"> &nbsp; 
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

        $script = '$(\'#image-group-'.$this->dbField.$index.'\').remove();';
        if ($this->isRequired) {
            $script .= '$(\'#'.$this->dbField.$index.'\').prop(\'required\', \'required\')';
        }

        $input = '<div class="row">
            <div class="col-sm-3">
                <input type="file" class="'.\implode(' ', $this->classes).'" id="'.$this->dbField.$index.'" name="'.$key.'['.$this->dbField.'][]" '.($this->isRequired && !$value ? 'required' : '').' accept="'.$this->accept.'" '.$this->raw.' '.$this->inlineCSS.' />
            </div>';

        if ($value) {
            if (FileManager::isImage($value)) {
                $file = '<img src="'.asset($value).'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
            } else {
                $file = '<i class="fa '.FileManager::getFileIcon($value).' fa-5x"></i>';
            }

            $input .= '<div class="col-sm-6" id="image-group-'.$this->dbField.$index.'"> &nbsp; 
                <a href="'.asset($value).'" target="_blank">'.$file.'</a>
                <input type="hidden" name="'.$key.'['.$this->dbField.'][]" value="'.$value.'">
                <button class="close pull-right" aria-hidden="true" type="button" onclick="'.$script.'"><i class="fa fa-times"></i></button>
            </div>';
        }

        $input .= '</div>';

        return $input;
    }
}
