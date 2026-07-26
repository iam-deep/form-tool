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

    private static function getConfigs($width, $height): void
    {
        self::$cachePath = removeSlash(config('form-tool.imageCachePath', self::$cachePath) ?: self::$cachePath);
        self::$width = $width ?: (config('form-tool.imageCacheWidth', self::$width) ?: self::$width);
        self::$height = $height ?: (config('form-tool.imageCacheHeight', self::$height) ?: self::$height);
        self::$memoryLimit = config('form-tool.memoryLimit', self::$memoryLimit) ?: self::$memoryLimit;
    }

    public static function resize(
        $imagePath,
        $width = null,
        $height = null,
        ?string $disk = null,
        ?string $visibility = null,
    ): ?string {
        return self::transform($imagePath, $width, $height, $disk, $visibility, 'resize');
    }

    public static function fit(
        $imagePath,
        $width = null,
        $height = null,
        ?string $disk = null,
        ?string $visibility = null,
    ): ?string {
        return self::transform($imagePath, $width, $height, $disk, $visibility, 'fit');
    }

    public static function getCachedImage(
        $imagePath,
        ?string $disk = null,
        ?string $visibility = null,
    ): ?string {
        self::getConfigs(null, null);
        [, $cacheImagePath] = self::getPath($imagePath);

        return FileManager::exists($cacheImagePath, $disk) ? $cacheImagePath : null;
    }

    public static function clearCache(?string $disk = null): bool
    {
        self::getConfigs(null, null);

        return FileManager::deleteDirectory(self::$cachePath, $disk);
    }

    private static function transform(
        $imagePath,
        $width,
        $height,
        ?string $disk,
        ?string $visibility,
        string $operation,
    ): ?string {
        if (! is_string($imagePath) || ! FileManager::exists($imagePath, $disk)) {
            return null;
        }

        if (! self::isResizable($imagePath)) {
            return $imagePath;
        }

        self::getConfigs($width, $height);
        [, $cacheImagePath] = self::getPath($imagePath);

        if (FileManager::exists($cacheImagePath, $disk)) {
            return $cacheImagePath;
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'form-tool-image-');
        if ($temporaryPath === false) {
            Log::error('Unable to create a temporary file for image processing.');

            return null;
        }

        $source = null;
        $destination = null;

        try {
            @ini_set('memory_limit', self::$memoryLimit);
            $source = FileManager::readStream($imagePath, $disk);
            $destination = fopen($temporaryPath, 'wb');

            if (! is_resource($source) || ! is_resource($destination)) {
                throw new \RuntimeException('Unable to stream image for processing.');
            }

            stream_copy_to_stream($source, $destination);
            fclose($source);
            fclose($destination);
            $source = $destination = null;

            $image = Image::make($temporaryPath);
            if ($operation === 'fit') {
                $image->fit(self::$width, self::$height);
            } else {
                $image->resize(self::$width, self::$height, function ($constraint) {
                    $constraint->aspectRatio();
                });
            }

            $extension = strtolower(pathinfo($cacheImagePath, PATHINFO_EXTENSION));
            $contents = (string) $image->encode($extension ?: null);

            if (! FileManager::write($cacheImagePath, $contents, $disk, $visibility)) {
                throw new \RuntimeException('Unable to write cached image.');
            }

            return $cacheImagePath;
        } catch (\Throwable $e) {
            Log::error($e->getMessage().' File: '.$imagePath);

            return null;
        } finally {
            if (is_resource($source)) {
                fclose($source);
            }
            if (is_resource($destination)) {
                fclose($destination);
            }
            if (file_exists($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    private static function getPath($imagePath): array
    {
        $pathinfo = pathinfo($imagePath);
        $path = self::$cachePath.'/'.($pathinfo['dirname'] ?? '');
        $filename = ($pathinfo['filename'] ?? 'image').'-'.self::$width.'x'.self::$height.'.'.($pathinfo['extension'] ?? 'jpg');

        return [trim($path, '/'), trim($path.'/'.$filename, '/')];
    }

    private static function isResizable($file): bool
    {
        $exts = 'jpg,jpeg,png,webp,gif,bmp,tif';
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        return $ext && in_array($ext, explode(',', $exts), true);
    }
}
