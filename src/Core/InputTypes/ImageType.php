<?php

namespace Biswadeep\FormTool\Core\InputTypes;

use Biswadeep\FormTool\Support\FileManager;
use Illuminate\Validation\Rule;

class ImageType extends FileType
{
    public int $type = InputType::Image;
    public string $typeInString = 'image';

    private $maxWidth;
    private $maxHeight;
    private $ratio;

    public function __construct()
    {
        parent::__construct();

        $this->accept('image/*');
    }

    public function dimensions($maxWidth = null, $maxHeight = null)
    {
        if ($maxWidth) {
            $this->maxWidth = $maxWidth;
        }

        if ($maxHeight) {
            $this->maxHeight = $maxHeight;
        }

        return $this;
    }

    public function ratio($ratio)
    {
        $this->ratio = $ratio;

        return $this;
    }

    public function getValidations($type)
    {
        $validations = parent::getValidations($type);

        if (\in_array('file', $validations)) {
            $validations[] = 'image';

            $flag = false;
            $rule = Rule::dimensions();
            if ($this->maxWidth) {
                $rule->maxWidth($this->maxWidth);
                $flag = true;
            }

            if ($this->maxHeight) {
                $rule->maxHeight($this->maxHeight);
                $flag = true;
            }

            if ($this->ratio) {
                $rule->ratio($this->ratio);
                $flag = true;
            }

            if ($flag) {
                $validations[] = $rule;
            }

            $imageTypes = FileManager::getImageTypes();
            if ($imageTypes) {
                $validations['mimes'] = 'mimes:'.$imageTypes;
            }
        }

        return $validations;
    }

    public function getTableValue()
    {
        if ($this->value) {
            if (FileManager::isImage($this->value)) {
                return '<a href="'.asset($this->value).'" target="_blank"><img class="img-thumbnail" src="'.asset($this->value).'" style="max-height:150px;max-width:150px;"></a>';
            }

            return parent::getTableValue($this->value);
        }

        return null;
    }
}
