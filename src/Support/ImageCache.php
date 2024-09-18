<?php

namespace Deep\FormTool\Support;

use Illuminate\Support\Facades\Log;
use Intervention\Image\Facades\Image;

class ImageCache
{
    protected static string $cachePath = 'cache';

    protected static int $width = 150;
    protected static int $height = 150;

    protected static string $memoryLimit = '512M';

    private static function getConfigs($width, $height)
    {
        // Let's get the configs
        self::$cachePath = removeSlash(\config('form-tool.imageCachePath', self::$cachePath) ?: self::$cachePath);
        self::$width = $width ?: (\config('form-tool.imageCacheWidth', self::$width) ?: self::$width);
        self::$height = $height ?: (\config('form-tool.imageCacheHeight', self::$height) ?: self::$height);
        self::$memoryLimit = \config('form-tool.memoryLimit', self::$memoryLimit) ?: self::$memoryLimit;
    }

    public static function resize($imagePath, $width = null, $height = null)
    {
        if (! \file_exists($imagePath)) {
            return null;
        }

        if (! self::isResizable($imagePath)) {
            return $imagePath;
        }

        self::getConfigs($width, $height);

        [$path, $cacheImagePath] = self::getPath($imagePath);

        // If file exists let's return
        if (\file_exists($cacheImagePath)) {
            return $cacheImagePath;
        }

        Directory::create($path);

        try {
            @\ini_set('memory_limit', self::$memoryLimit);

            // open an image file
            $img = Image::make($imagePath);

            // resize image instance
            $img->resize(self::$width, self::$height, function ($constraint) {
                $constraint->aspectRatio();
            });

            // insert a watermark
            // $img->insert('public/watermark.png');

            // save image in desired format
            $img->save($cacheImagePath);
        } catch (\Exception $e) {
            Log::error($e->getMessage().' File: '.$imagePath);
            // throw $e;

            return null;
        }

        return $cacheImagePath;
    }

    public static function fit($imagePath, $width = null, $height = null)
    {
        if (! \file_exists($imagePath)) {
            return null;
        }

        if (! self::isResizable($imagePath)) {
            return $imagePath;
        }

        self::getConfigs($width, $height);

        [$path, $cacheImagePath] = self::getPath($imagePath);

        // If file exists let's return
        if (\file_exists($cacheImagePath)) {
            return $cacheImagePath;
        }

        Directory::create($path);

        try {
            @\ini_set('memory_limit', self::$memoryLimit);

            // open an image file
            $img = Image::make($imagePath);

            // resize image instance
            $img->fit(self::$width, self::$height);

            // insert a watermark
            // $img->insert('public/watermark.png');

            // save image in desired format
            $img->save($cacheImagePath);
        } catch (\Exception $e) {
            Log::error($e->getMessage().' File: '.$imagePath);
            // throw $e;

            return null;
        }

        return $cacheImagePath;
    }

    private static function getPath($imagePath)
    {
        $pathinfo = \pathinfo($imagePath);

        // Create the cache path
        $path = self::$cachePath.'/'.$pathinfo['dirname'];

        // Create the cache filename
        $filename = $pathinfo['filename'].'-'.self::$width.'x'.self::$height.'.'.$pathinfo['extension'];

        // Full path of the cache image
        $cacheImagePath = $path.'/'.$filename;

        return [$path, $cacheImagePath];
    }

    public static function clearCache()
    {
        self::getConfigs(null, null);

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(self::$cachePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }

        \rmdir(self::$cachePath);
    }

    private static function isResizable($file)
    {
        $exts = 'jpg,jpeg,png,webp,gif,bmp,tif';

        $ext = \strtolower(\pathinfo($file, PATHINFO_EXTENSION));

        if ($ext && \in_array($ext, \explode(',', $exts))) {
            return true;
        }

        return false;
    }
}
