<?php

namespace Biswadeep\FormTool\Http\Libraries\InputTypes;

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
        $this->maxSizeInKb = \trim($maxSizeInKb);

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
            }
            else {
                $validations[] = 'nullable';
            }
        }
        else {
            if ($this->isRequired && ! $request->{$this->dbField}) {
                $validations[] = 'required';
            }
            else {
                $validations[] = 'nullable';
            }
        }

        if ($request->file($this->dbField)) {
            $validations[] = 'file';
        }

        if ($this->maxSizeInKb > 0) {
            $validations[] = 'max:' . $this->maxSizeInKb;
        }

        $allowedTypes = config('form-tool.allowedTypes');
        if ($allowedTypes)
            $validations['mimes'] = 'mimes:' . $allowedTypes;

        return $validations;
    }

    public function beforeStore(object $newData)
    {
        $request = request();
        $file = $request->file($this->dbField);

        if ($file) {
            $destinationPath = $this->getUploadPath();
            $filename = $this->addHypens($file->getClientOriginalName());

            return $this->uploadFile($file, $destinationPath, $filename, true);
        }

        return null;
    }

    protected function uploadFile($file, $destinationPath, $filename, $flagCheck)
    {
        // If same file name exist then increment the file name
        if ($flagCheck) {
            $i = 2;
            while(\file_exists($destinationPath . $filename)) {
                $pathinfo = \pathinfo($filename);

                if (isset($pathinfo['filename'])) {
                    $filename = $pathinfo['filename'] . '_' . $i++;

                    if (isset($pathinfo['extension']))
                        $filename .= '.' . $pathinfo['extension'];
                }
                else {
                    $filename .= '_' . $i++;
                }
            }
        }

        $file->move($destinationPath, $filename);

        return $destinationPath . $filename;
    }

    public function beforeUpdate(object $oldData, object $newData)
    {
        $request = request();
        $file = $request->file($this->dbField);

        if ($file) {
            $flagCheck = true;

            $destinationPath = $this->getUploadPath();
            $filename = $this->addHypens($file->getClientOriginalName());

            // Let's replace the old file if exists
            if (isset($oldData->{$this->dbField}) && $oldData->{$this->dbField}) {
                $pathinfo = \pathinfo($oldData->{$this->dbField});

                $ext = $pathinfo['extension'] ?? '';

                // Check the old file extention with new file
                if ($ext == $file->getClientOriginalExtension()) {
                    $flagCheck = false;

                    $destinationPath = $pathinfo['dirname'] . '/';
                    $filename = $pathinfo['basename'];
                }

                /* TODO: Delete the cache image
                
                $cacheImage = $destinationPath . $pathinfo['filename'].'-150x150.'.$pathinfo['extension'];
                $this->deleteFile($cacheImage);
                */
            }

            return $this->uploadFile($file, $destinationPath, $filename, $flagCheck);
        }

        $oldFile = $request->{$this->dbField};
        
        return $oldFile ?? '';
    }

    public function afterUpdate(object $oldData, object $newData)
    {
        $oldFile = $oldData->{$this->dbField} ?? null;
        $newFile = $newData->{$this->dbField} ?? null;

        if ($oldFile != $newFile) {
            $this->deleteFile($oldFile);
        }
    }

    public function afterDestroy(object $oldData)
    {
        $this->deleteFile($oldData->{$this->dbField} ?? null);
    }

    private function addHypens($value)
    {
        do {
            $value = str_replace(['-', '--'], '-', $value);
        } while(false !== strpos($value, '--'));

        return $value;
    }

    public function getTableValue()
    {
        if ($this->value)
            return '<a href="'. asset($this->value) .'" target="_blank"><i class="fa '. $this->getFileIcon($this->value) .' fa-2x"></i></a>';

        return null;
    }

    public function getHTML()
    {
        $value = old($this->dbField, $this->value);

        $script = '$(\'#image-group-'. $this->dbField .'\').remove();';
        if ($this->isRequired)
            $script .= '$(\'#'. $this->dbField .'\').prop(\'required\', \'required\')';

        $input = '<div class="row">
            <div class="col-sm-3">
                <input type="file" class="'. implode(' ', $this->classes) .'" id="'. $this->dbField .'" name="'. $this->dbField .'" '. ($this->isRequired && !$this->value ? 'required' : '') .' accept="'. $this->accept .'" '. $this->raw  .' '. $this->inlineCSS  .' />
            </div>';

        if ($this->value) {
            if ($this->isImage($this->value)) {
                $file = '<img src="'. asset($value) .'" class="img-thumbnail" style="max-height:150px;max-width:150px;">';
            }
            else {
                $file = '<i class="fa '. $this->getFileIcon($this->value) .' fa-5x"></i>';
            }

            $input .= '<div class="col-sm-6" id="image-group-'. $this->dbField .'"> &nbsp; 
                <a href="'. asset($value) .'" target="_blank">'. $file .'</a>
                <input type="hidden" name="'. $this->dbField .'" value="'. $this->value .'">
                <button class="close pull-left" aria-hidden="true" type="button" onclick="'. $script .'"><i class="fa fa-times"></i></button>
            </div>';
        }

        $input .= '</div>';

        return $this->htmlParentDiv($input);
    }

    protected function deleteFile($file)
    {
        if ($file) {
            try {
                if (\file_exists($file))
                    \unlink($file);
            }
            catch (\Exception $e) {
                // TODO: log
            }
        }
    }

    protected function getUploadPath() : string
	{
		$uploadDir = \trim(config('form-tool.uploadPath'));

        if (!$uploadDir) {
			throw new \Exception("'uploadPath' not set at config/form-tool.php");
        }

        $uploadDir = \str_replace('/', '', $uploadDir);

        if ( ! file_exists($uploadDir)) {
            try {
                mkdir($uploadDir);
                $this->restrictDirectoryAccess($uploadDir);
            }
            catch (\Exception $e) {
                throw new \Exception('Failed to create directory: ' . $uploadDir);
            }
        }

		if ( ! is_writable($uploadDir)) {
			throw new \Exception('Upload Directory not writable: ' . $uploadDir);
        }
		
		$path = [];
        if ($this->path) {
            $dirs = array_filter(explode('/', $this->path));
			foreach ($dirs as $dir) {
				//if ($dir && ! in_array($dir, $exclude))
					$path[] = $dir;
            }
        }

        $subDirDate = \trim(config('form-tool.uploadSubDirFormat', 'm-Y'));
		if ($subDirDate) {
            $format = str_replace([' ', '  '], '-', $subDirDate);
            if ($format) {
                $path[] = date($format);
            }
        }
		
        $uploadPath = $uploadDir . '/';
		$subDirs = implode('/', array_filter($path));
        if ($subDirs) {
		    $uploadPath = $uploadPath . $subDirs . '/';
        }
        
		if ($uploadPath && ! file_exists($uploadPath)) {
            try {
                mkdir($uploadPath, 0777, true);
                $this->restrictDirectoryAccess($uploadPath);
            }
            catch (\Exception $e) {
                throw new \Exception('Failed to create directory: ' . $uploadPath . '. ' . $e->getMessage());
            }
		}

		return $uploadPath;
	}

    protected function restrictDirectoryAccess(string $path)
	{
        $dirs = array_filter(explode('/', $path));

        $parentPath = '';
		while (count($dirs)) {
			$currentDir = array_shift($dirs);

			$indexFile = $parentPath . $currentDir . '/index.html';

			if ( ! file_exists($indexFile)) {
                try {
				    $handle = fopen($indexFile, 'w');
				
                    $fileData = '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><p>Directory access is forbidden.</p></body></html>';
                    fwrite($handle, $fileData);
                    fclose($handle);
                }
                catch (\Exception $e) {
                    // TODO: Log
                }
			}
			
			/* It can be used to create index file for all the sub directories under parent directory
            foreach (glob($currentDir . '/*') as $dir) {
				if (is_dir($dir))
					$dirs[] = $dir;
            }*/

            $parentPath .= $currentDir . '/';
		}
	}

    protected function isImage($file, $exts = null)
    {
        if (!$exts) 
            $exts = config('form-tool.imageTypes');

        $ext = pathinfo($file, PATHINFO_EXTENSION);

        if ($ext && in_array($ext, explode(',', $exts)))
            return true;

        return false;
    }

    protected function getFileIcon($file)
    {
        $ext = pathinfo($file, PATHINFO_EXTENSION);

        switch ($ext) {
            case 'pdf':
                return 'fa-file-pdf-o';
            
            case 'zip':
            case 'rar':
            case 'tar.gz':
                return 'fa-file-archive-o';
            
            case 'php':
            case 'html':
            case 'css':
            case 'js':
                return 'fa-file-code-o';

            case 'mkv':
            case 'flv':
            case 'avi':
            case '3gp':
                return 'fa-file-video-o';

            case 'mp3':
            case 'wv':
                return 'fa-file-audio-o';

            case 'jpg':
            case 'jpeg':
            case 'png':
            case 'webp':
            case 'gif':
            case 'svg':
            case 'bmp':
                return 'fa-file-image-o';

            case 'ppt':
            case 'pptx':
                return 'fa-file-powerpoint-o';

            case 'csv';
            case 'xsl';
            case 'xslx';
                return 'fa-file-excel-o';

            case 'doc':
            case 'docx':
                return 'fa-file-word-o';

            default:
                return 'fa-file-text';
        }
    }
}