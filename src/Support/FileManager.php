<?php

namespace Deep\FormTool\Support;

use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;

class FileManager
{
    protected static string $uploadPath = 'storage';
    protected static string $uploadSubDirFormat = 'm-Y';
    protected static string $allowedTypes = 'jpg,jpeg,png,webp,gif,svg,bmp,tif,pdf,docx,doc,xls,xlsx,rtf,txt,ppt,csv,'.
        'pptx,webm,mkv,flv,vob,avi,mov,mp3,mp4,m4p,mpg,mpeg,mp2,svi,3gp,rar,zip,psd,dwg,eps,xlr,db,dbf,mdb,html,tar.gz,zipx';
    protected static string $imageTypes = 'jpg,jpeg,png,webp,gif,svg,bmp,tif';

    protected static $cropWidth = null;
    protected static $cropHeight = null;
    protected static $cropPosition = 'center';

    public static function setCrop($width, $height = null, $position = 'center')
    {
        if ($height === null) {
            $height = $width;
        }

        self::$cropWidth = $width;
        self::$cropHeight = $height;
        self::$cropPosition = $position;

        return self::class;
    }

    public static function getAllowedTypes()
    {
        return \trim(config('form-tool.allowedTypes', self::$allowedTypes));
    }

    public static function getImageTypes()
    {
        return \trim(config('form-tool.imageTypes', self::$imageTypes));
    }

    public static function uploadFile(?UploadedFile $file, ?string $subPath, string $oldFilePath = null)
    {
        if ($file) {
            $flagCheck = true;

            $destinationPath = FileManager::getUploadPath($subPath);
            $filename = self::filterFilename($file->getClientOriginalName());

            // Let's replace the old file if exists
            /*if ($oldFilePath) {
                $pathinfo = \pathinfo($oldFilePath);

                $ext = $pathinfo['extension'] ?? '';

                // Check the old file extension with new file
                if ($ext == $file->getClientOriginalExtension()) {
                    $flagCheck = false;

                    $destinationPath = \ltrim($pathinfo['dirname'], '/').'/';
                    $filename = $pathinfo['basename'];
                }

                // TODO: Delete the cache image

                // $cacheImage = $destinationPath . $pathinfo['filename'].'-150x150.'.$pathinfo['extension'];
                // FileManager::deleteFile($cacheImage);
            }*/

            return FileManager::doUpload($file, $destinationPath, $filename, $flagCheck);
        }

        return null;
    }

    private static function doUpload($file, $destinationPath, $filename, $flagCheck = true)
    {
        $mainFilename = $filename;

        // If same file name exist then increment the file name
        if ($flagCheck) {
            $i = 2;
            while (\file_exists($destinationPath.$filename)) {
                $filename = $mainFilename;
                $pathinfo = \pathinfo($filename);

                if (isset($pathinfo['filename'])) {
                    $filename = $pathinfo['filename'].'-'.$i++;

                    if (isset($pathinfo['extension'])) {
                        $filename .= '.'.$pathinfo['extension'];
                    }
                } else {
                    $filename .= '-'.$i++;
                }
            }
        }

        if (self::isImage($filename) && self::$cropWidth) {
            $image = Image::make($file);

            // perform orientation using intervention, this is needed for direct upload from mobile camera capture
            $image->orientate();

            $image->fit(self::$cropWidth, self::$cropHeight, null, self::$cropPosition);

            $image->save($destinationPath.$filename);
        } else {
            $file->move($destinationPath, $filename);
        }

        // set back to null
        self::$cropWidth = null;
        self::$cropHeight = null;
        self::$cropPosition = 'center';

        return $destinationPath.$filename;
    }

    public static function deleteFile($file)
    {
        if ($file) {
            try {
                if (\file_exists($file)) {
                    \unlink($file);
                }
            } catch (\Exception $e) {
                $e;
            }
        }
    }

    public static function getUploadPath($subPath = '', $uploadDir = ''): string
    {
        $uploadDir = $uploadDir ?: \trim(config('form-tool.uploadPath', self::$uploadPath));

        // Remove the first / (slash)
        if (0 === strpos($uploadDir, '/')) {
            $uploadDir = substr($uploadDir, 1);
        }

        // This was preventing to create dynamic upload path
        // $uploadDir = \str_replace('/', '', $uploadDir);
        $uploadPath = '';

        if ($uploadDir) {
            Directory::create($uploadDir);

            $uploadPath = $uploadDir.'/';
        }

        $path = [];
        if ($subPath) {
            $dirs = array_filter(\explode('/', $subPath));
            foreach ($dirs as $dir) {
                //if ($dir && ! in_array($dir, $exclude))
                $path[] = $dir;
            }
        }

        $subDirDate = \trim(config('form-tool.uploadSubDirFormat', 'm-Y'));
        if ($subDirDate) {
            $format = \str_replace([' ', '  '], '-', $subDirDate);
            if ($format) {
                $path[] = \date($format);
            }
        }

        $subDirs = \implode('/', array_filter($path));
        if ($subDirs) {
            $uploadPath = $uploadPath.$subDirs.'/';
        }

        if ($uploadPath) {
            Directory::create($uploadPath);
        }

        return $uploadPath;
    }

    public static function isImage($file, $exts = null)
    {
        if (! $exts) {
            $exts = self::getImageTypes();
        }

        $ext = \strtolower(\pathinfo($file, PATHINFO_EXTENSION));

        if ($ext && \in_array($ext, \explode(',', $exts))) {
            return true;
        }

        return false;
    }

    public static function getFileIcon($file)
    {
        $ext = \pathinfo($file, PATHINFO_EXTENSION);

        $icons = config('form-tool.icons', []);
        if ($icons && is_array($icons)) {
            if (isset($icons[$ext])) {
                return $icons[$ext];
            }

            if (isset($icons['*'])) {
                return $icons['*'];
            }
        }

        return 'ICON NOT SPECIFIED';
    }

    public static function filterFilename($value)
    {
        do {
            $value = \str_replace([' ', '--'], '-', $value);
        } while (false !== \strpos($value, '--'));

        $value = \preg_replace("/[^a-z0-9\_\-\.]/i", '', $value);

        return $value;
    }
}
