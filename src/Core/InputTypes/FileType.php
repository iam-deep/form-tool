<?php

namespace Deep\FormTool\Core\InputTypes;

use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Support\FileManager;
use Deep\FormTool\Support\ImageCache;

class FileType extends BaseInputType
{
    public int $type = InputType::FILE;
    public string $typeInString = 'file';

    private string $path = '';
    private float $maxSizeInKb = 0;
    private $accept = '';

    private $cropWidth = null;
    private $cropHeight = null;
    private string $cropPosition = 'center';

    protected string $placeholderImage = 'assets/form-tool/images/placeholder.png';

    public function __construct()
    {
        parent::__construct();

        $this->classes = [];

        $this->maxSizeInKb = config('form-tool.maxFileUploadSize', 1024 * 5);
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

    public function maxUploadSize(float $maxSizeInKb)
    {
        $this->maxSizeInKb = $maxSizeInKb;

        return $this;
    }

    public function crop($width, $height = null, $position = 'center')
    {
        $this->cropWidth = $width;
        $this->cropHeight = $height;
        $this->cropPosition = $position;

        return $this;
    }

    public function imagePlaceholder(string $relativePath)
    {
        $this->placeholderImage = $relativePath;

        return $this;
    }

    /**
     * Validate and accept file type.
     *
     * @param  string  $accept  mimes like pdf,docs,jpg
     * @return Deep\FormTool\Core\InputTypes\FileType
     **/
    public function accept(string $accept)
    {
        $this->accept = \trim($accept);

        return $this;
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

            if ($this->accept) {
                $validations['mimes'] = 'mimes:'.$this->accept;
            } else {
                $allowedTypes = FileManager::getAllowedTypes();
                if ($allowedTypes && ! isset($validations['mimes'])) {
                    $validations['mimes'] = 'mimes:'.$allowedTypes;
                }
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

            $this->value = FileManager::setCrop($this->cropWidth, $this->cropHeight, $this->cropPosition)::uploadFile($file, $this->path);

            return $this->value;
        } else {
            $file = $request->file($this->dbField);
            $this->value = FileManager::setCrop($this->cropWidth, $this->cropHeight, $this->cropPosition)::uploadFile($file, $this->path);

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

            $filename = FileManager::setCrop($this->cropWidth, $this->cropHeight, $this->cropPosition)::uploadFile($file, $this->path, $oldFile);
            if ($filename !== null) {
                $this->value = $filename;

                return $this->value;
            }
        } else {
            $oldFile = $request->post($this->dbField);
            $file = $request->file($this->dbField);

            $filename = FileManager::setCrop($this->cropWidth, $this->cropHeight, $this->cropPosition)::uploadFile($file, $this->path, $oldFile);
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
        if (! $value) {
            return null;
        }

        if (FileManager::isImage($this->value)) {
            $image = ImageCache::resize($this->value);

            $maxWidth = config('form-tool.imageThumb.table.maxWidth', '50px');
            $maxHeight = config('form-tool.imageThumb.table.maxHeight', '50px');

            return '<a href="'.asset($this->value).'" target="_blank">
                        <img src="'.asset($image).'" class="img-thumbnail" style="max-height:'.$maxHeight.';max-width:'.$maxWidth.';">
                    </a>';
        } else {
            return '<a href="'.asset($this->value).'" target="_blank">
                <i class="'.FileManager::getFileIcon($this->value).' fa-3x"></i>
            </a>';
        }
    }

    public function getExportValue($value)
    {
        return $value;
    }

    public function getLoggerValue(string $action, $oldValue = null)
    {
        $newValue = $this->value;

        if ($action == 'update') {
            if ($oldValue != $newValue) {
                return [
                    'type' => $this->typeInString,
                    'data' => [$oldValue ?? '', $newValue ?? ''],
                ];
            }

            return '';
        }

        return $newValue !== null ? ['type' => $this->typeInString, 'data' => $newValue] : '';
    }

    public function getHTML()
    {
        $value = old($this->dbField, $this->value);

        $isImageField = InputType::IMAGE == $this->type;

        $noImage = $imageCache = asset($this->placeholderImage);
        $isImage = FileManager::isImage($value);
        if ($isImageField || $isImage) {
            if ($value) {
                $imageCache = asset(ImageCache::resize($value));
            } else {
                $imageCache = $noImage;
            }
        }

        $accept = $this->accept;
        $formats = 'png, jpg, pdf & docs';
        if ($this->accept == 'image/*') {
            $formats = 'png, jpg, svg & webp';
        } elseif ($this->accept) {
            $mimes = array_filter(explode(',', $this->accept));
            $formats = implode(', ', $mimes);
            $accept = implode(',', array_map(fn ($mime) => '.'.$mime, $mimes));
        }

        $data['input'] = (object) [
            'type' => 'single',
            'column' => $this->dbField,
            'rawValue' => $value,
            'value' => asset($value),
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,

            // File Specific
            'maxSize' => $this->maxSizeInKb,
            'isImageField' => $isImageField,
            'accept' => $accept,
            'formats' => $formats,
            'isImage' => $isImage,
            'imageCache' => $imageCache,
            'noImage' => $noImage,
            'icon' => FileManager::getFileIcon($value),
        ];

        return $this->htmlParentDiv(\view('form-tool::form.input_types.file', $data)->render());
    }

    public function getHTMLMultiple($key, $index, $oldValue)
    {
        $value = $oldValue ?? $this->value;

        $groupId = $key.'-group-'.$this->dbField.'-'.$index;
        $id = $key.'-'.$this->dbField.'-'.$index;
        $name = $key.'['.$index.']['.$this->dbField.']';

        $isImageField = InputType::IMAGE == $this->type;

        $imageCache = null;
        $noImage = asset($this->placeholderImage);
        $isImage = FileManager::isImage($value);
        if ($isImageField || $isImage) {
            if ($value) {
                $imageCache = asset(ImageCache::resize($value));
            } else {
                $imageCache = $noImage;
            }
        }

        if ($this->isRequired) {
            if (! $value) {
                $this->raw('required');
            } else {
                $this->removeRaw('required');
            }
        }

        $accept = $this->accept;
        $formats = 'png, jpg, pdf & docs';
        if ($this->accept == 'image/*') {
            $formats = 'png, jpg, svg & webp';
        } elseif ($this->accept) {
            $mimes = array_filter(explode(',', $this->accept));
            $formats = implode(', ', $mimes);
            $accept = implode(',', array_map(fn ($mime) => '.'.$mime, $mimes));
        }

        $data['input'] = (object) [
            'type' => 'multiple',
            'key' => $key,
            'index' => $index,
            'column' => $this->dbField,
            'rawValue' => $value,
            'value' => asset($value),
            'oldValue' => $oldValue,
            'id' => $id,
            'name' => $name,
            'classes' => \implode(' ', $this->classes),
            'raw' => $this->raw.$this->inlineCSS,
            'isRequired' => $this->isRequired,

            // File Specific
            'groupId' => $groupId,
            'maxSize' => $this->maxSizeInKb,
            'isImageField' => $isImageField,
            'accept' => $accept,
            'formats' => $formats,
            'isImage' => $isImage,
            'imageCache' => $imageCache,
            'noImage' => $noImage,
            'icon' => FileManager::getFileIcon($value),
        ];

        return \view('form-tool::form.input_types.file', $data)->render();

        /*$value = $oldValue ?? $this->value;

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
                <input type="file" class="'.\implode(' ', $this->classes).'" id="'.$inputId.'" name="'.$name.
                    '" accept="'.$this->accept.'" '.$this->raw.$this->inlineCSS.' />
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
                $file = '<i class="'.FileManager::getFileIcon($value).'"></i>';
            }

            $input .= '<div class="col-sm-6" id="'.$groupId.'"> &nbsp;
                <a href="'.asset($value).'" target="_blank">'.$file.'</a>
                <input type="hidden" name="'.$name.'" value="'.$value.'">
                <button class="close pull-right" aria-hidden="true" type="button" onclick="'.$script.
                    '"><i class="fa fa-times"></i></button>
            </div>';
        }

        $input .= '</div>';

        return $input;*/
    }
}
