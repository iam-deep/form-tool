<?php

namespace Biswadeep\FormTool\Support;

use Illuminate\Http\UploadedFile;

class FileManager
{
    protected static string $uploadPath = 'uploads';
    protected static string $uploadSubDirFormat = 'm-Y';
    protected static string $allowedTypes = 'jpg,jpeg,png,webp,gif,svg,bmp,tif,pdf,docx,doc,xls,xlsx,rtf,txt,ppt,csv,pptx,webm,mkv,flv,vob,avi,mov,mp3,mp4,m4p,mpg,mpeg,mp2,svi,3gp,rar,zip,psd,dwg,eps,xlr,db,dbf,mdb,html,tar.gz,zipx';
    protected static string $imageTypes = 'jpg,jpeg,png,webp,gif,svg,bmp,tif';

    public static function getAllowedTypes()
    {
        return \trim(config('form-tool.allowedTypes', self::$allowedTypes));
    }

    public static function getImageTypes()
    {
        return \trim(config('form-tool.imageTypes', self::$imageTypes));
    }

    public static function uploadFile(?UploadedFile $file, string $subPath, string $oldFilePath = null)
    {
        if ($file) {
            $flagCheck = true;

            $destinationPath = FileManager::getUploadPath($subPath);
            $filename = self::addHypens($file->getClientOriginalName());

            // Let's replace the old file if exists
            /*if ($oldFilePath) {
                $pathinfo = \pathinfo($oldFilePath);

                $ext = $pathinfo['extension'] ?? '';

                // Check the old file extention with new file
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

    private function doUpload($file, $destinationPath, $filename, $flagCheck = true)
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

        $file->move($destinationPath, $filename);

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
                // TODO: log
            }
        }
    }

    public static function getUploadPath($subPath = ''): string
    {
        $uploadDir = \trim(config('form-tool.uploadPath', self::$uploadPath));

        $uploadDir = \str_replace('/', '', $uploadDir);
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

            case 'csv':
            case 'xsl':
            case 'xslx':
                return 'fa-file-excel-o';

            case 'doc':
            case 'docx':
                return 'fa-file-word-o';

            default:
                return 'fa-file-text';
        }
    }

    private function addHypens($value)
    {
        do {
            $value = \str_replace([' ', '--'], '-', $value);
        } while (false !== \strpos($value, '--'));

        return $value;
    }
}
