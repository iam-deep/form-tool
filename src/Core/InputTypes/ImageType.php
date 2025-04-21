<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Support\FileManager;
use Deep\FormTool\Support\ImageCache;
use Illuminate\Validation\Rule;

class ImageType extends FileType
{
    public int $type = InputType::IMAGE;
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

    public function profile(?string $imagePath = null)
    {
        $this->placeholderImage = $imagePath ?: 'assets/form-tool/images/user.png';

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

    // public function getNiceValue($value)
    // {
    //     if ($value) {
    //         if (FileManager::isImage($value)) {
    //             $image = ImageCache::resize($value);

    //             $maxWidth = config('form-tool.imageThumb.table.maxWidth', '50px');
    //             $maxHeight = config('form-tool.imageThumb.table.maxHeight', '50px');

    //             return '<a href="'.asset($value).'" target="_blank"><img class="img-thumbnail" '.
    //                 'src="'.asset($image).'" style="max-height:'.$maxHeight.';max-width:'.$maxWidth.';"></a>';
    //         }

    //         return parent::getNiceValue($value);
    //     }

    //     return null;
    // }
}
