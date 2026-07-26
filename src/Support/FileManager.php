<?php

namespace Deep\FormTool\Support;

use DateTimeInterface;
use Deep\FormTool\Contracts\PrivateFileUrlResolver;
use Deep\FormTool\Exceptions\FileUploadException;
use Deep\FormTool\Exceptions\FormToolException;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public static function diskName(?string $disk = null): string
    {
        $disk = trim((string) ($disk ?: config('form-tool.filesystem.disk', 'local')));

        if ($disk === '') {
            throw new FormToolException('File storage disk is not configured.');
        }

        return $disk;
    }

    public static function visibility(?string $visibility = null): string
    {
        $visibility = trim((string) ($visibility ?: config('form-tool.filesystem.visibility', 'public')));

        if (! in_array($visibility, ['public', 'private'], true)) {
            throw new FormToolException('File visibility must be public or private.');
        }

        return $visibility;
    }

    public static function uploadFile(
        ?UploadedFile $file,
        ?string $subPath,
        ?string $oldFilePath = null,
        ?string $disk = null,
        ?string $visibility = null,
    ): ?string {
        if (! $file) {
            self::resetCrop();

            return null;
        }

        try {
            $destinationPath = self::getUploadPath($subPath);
            $filename = self::filterFilename($file->getClientOriginalName());

            return self::doUpload(
                $file,
                $destinationPath,
                $filename,
                self::diskName($disk),
                self::visibility($visibility),
            );
        } catch (Exception $e) {
            if ($e instanceof FileUploadException || $e instanceof FormToolException) {
                throw $e;
            }

            $size = config('form-tool.maxFileUploadSize', 1024 * 5) / 1024;

            throw new FormToolException('Upload Error! Please upload photo/file less than '.$size.'MB.');
        } finally {
            self::resetCrop();
        }
    }

    private static function doUpload(
        UploadedFile $file,
        string $destinationPath,
        string $filename,
        string $disk,
        string $visibility,
    ): string {
        $filename = self::uniqueFilename($destinationPath, $filename, $disk, '-');
        $key = self::normalizeKey($destinationPath.$filename);

        try {
            if (self::isImage($filename) && self::$cropWidth) {
                $image = Image::make($file);
                $image->orientate();
                $image->fit(self::$cropWidth, self::$cropHeight, null, self::$cropPosition);
                $extension = strtolower($file->getClientOriginalExtension()) ?: null;
                $contents = $image->stream($extension);

                if (! self::write($key, $contents, $disk, $visibility)) {
                    throw new FileUploadException('Could not write uploaded image.');
                }
            } else {
                $stored = Storage::disk($disk)->putFileAs(
                    rtrim($destinationPath, '/'),
                    $file,
                    $filename,
                    ['visibility' => $visibility],
                );

                if ($stored === false) {
                    throw new FileUploadException('Could not write uploaded file.');
                }

                $key = self::normalizeKey($stored);
                Storage::disk($disk)->setVisibility($key, $visibility);
            }
        } catch (Exception $e) {
            if ($e instanceof FileUploadException) {
                throw $e;
            }

            throw new FileUploadException($e->getMessage());
        }

        return $key;
    }

    public static function copyFile(
        $file,
        ?string $disk = null,
        ?string $visibility = null,
        ?string $destinationDirectory = null,
    ): ?string {
        if (! is_string($file) || trim($file) === '') {
            return null;
        }

        $disk = self::diskName($disk);
        $file = self::normalizeKey($file);

        if (! self::exists($file, $disk)) {
            return null;
        }

        $pathinfo = pathinfo($file);
        if (empty($pathinfo['dirname']) || empty($pathinfo['filename'])) {
            return null;
        }

        $extension = isset($pathinfo['extension']) && $pathinfo['extension'] !== ''
            ? '.'.$pathinfo['extension']
            : '';
        if ($destinationDirectory !== null) {
            $destinationDirectory = rtrim(self::normalizeKey($destinationDirectory), '/');
            $base = $destinationDirectory.'/'.$pathinfo['filename'];
            $copy = $base.$extension;
            $index = 1;

            while (self::exists($copy, $disk)) {
                $copy = $base.'-'.($index++).$extension;
            }
        } else {
            $base = $pathinfo['dirname'].'/'.$pathinfo['filename'];
            $index = 2;

            do {
                $copy = $base.'_'.($index++).$extension;
            } while (self::exists($copy, $disk));
        }

        try {
            if (! Storage::disk($disk)->copy($file, $copy)) {
                return null;
            }

            Storage::disk($disk)->setVisibility(
                $copy,
                $visibility ? self::visibility($visibility) : Storage::disk($disk)->getVisibility($file),
            );
        } catch (Exception) {
            return null;
        }

        return $copy;
    }

    public static function deleteFile($file, ?string $disk = null): bool
    {
        if (! is_string($file) || trim($file) === '') {
            return true;
        }

        try {
            $file = self::normalizeKey($file);
            $filesystem = Storage::disk(self::diskName($disk));

            return ! $filesystem->exists($file) || $filesystem->delete($file);
        } catch (Exception) {
            return false;
        }
    }

    public static function exists(?string $path, ?string $disk = null): bool
    {
        if (! is_string($path) || trim($path) === '') {
            return false;
        }

        return Storage::disk(self::diskName($disk))->exists(self::normalizeKey($path));
    }

    public static function readStream(string $path, ?string $disk = null)
    {
        return Storage::disk(self::diskName($disk))->readStream(self::normalizeKey($path));
    }

    public static function write(
        string $path,
        mixed $contents,
        ?string $disk = null,
        ?string $visibility = null,
    ): bool {
        return Storage::disk(self::diskName($disk))->put(
            self::normalizeKey($path),
            $contents,
            ['visibility' => self::visibility($visibility)],
        );
    }

    public static function size(string $path, ?string $disk = null): int
    {
        return Storage::disk(self::diskName($disk))->size(self::normalizeKey($path));
    }

    public static function deleteDirectory(string $path, ?string $disk = null): bool
    {
        return Storage::disk(self::diskName($disk))->deleteDirectory(self::normalizeKey($path));
    }

    public static function url(
        ?string $path,
        ?string $disk = null,
        ?string $visibility = null,
    ): string {
        if (! is_string($path) || trim($path) === '') {
            return '';
        }

        $path = self::normalizeKey($path);
        $disk = self::diskName($disk);

        if (self::visibility($visibility) === 'public') {
            return Storage::disk($disk)->url($path);
        }

        $resolverClass = config('form-tool.filesystem.privateUrlResolver');
        if (! is_string($resolverClass)
            || ! is_a($resolverClass, PrivateFileUrlResolver::class, true)) {
            throw new FormToolException('Private file URL resolver is not configured.');
        }

        $minutes = max(1, (int) config('form-tool.filesystem.privateUrlTtlMinutes', 5));

        return app($resolverClass)->resolve($path, $disk, now()->addMinutes($minutes));
    }

    public static function temporaryUrl(
        string $path,
        ?string $disk = null,
        ?DateTimeInterface $expiresAt = null,
        array $options = [],
    ): string {
        $minutes = max(1, (int) config('form-tool.filesystem.privateUrlTtlMinutes', 5));

        return Storage::disk(self::diskName($disk))->temporaryUrl(
            self::normalizeKey($path),
            $expiresAt ?: now()->addMinutes($minutes),
            $options,
        );
    }

    public static function getUploadPath($subPath = '', $uploadDir = ''): string
    {
        $uploadDir = $uploadDir ?: \trim(config('form-tool.uploadPath', self::$uploadPath));
        $parts = [];

        if ($uploadDir !== '') {
            $parts[] = $uploadDir;
        }

        if ($subPath) {
            $parts[] = $subPath;
        }

        $subDirDate = \trim(config('form-tool.uploadSubDirFormat', self::$uploadSubDirFormat));
        if ($subDirDate !== '') {
            $parts[] = \date(\str_replace([' ', '  '], '-', $subDirDate));
        }

        $path = self::normalizeKey(implode('/', $parts));

        return $path === '' ? '' : $path.'/';
    }

    public static function isImage($file, $exts = null)
    {
        if (! $exts) {
            $exts = self::getImageTypes();
        }

        $ext = \strtolower(\pathinfo($file, PATHINFO_EXTENSION));

        return $ext && \in_array($ext, \explode(',', $exts));
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

        return \preg_replace("/[^a-z0-9\_\-\.]/i", '', $value);
    }

    private static function uniqueFilename(string $destinationPath, string $filename, string $disk, string $separator): string
    {
        $mainFilename = $filename;
        $index = 2;

        while (self::exists($destinationPath.$filename, $disk)) {
            $pathinfo = pathinfo($mainFilename);
            $filename = ($pathinfo['filename'] ?? $mainFilename).$separator.($index++);

            if (! empty($pathinfo['extension'])) {
                $filename .= '.'.$pathinfo['extension'];
            }
        }

        return $filename;
    }

    private static function normalizeKey(string $path): string
    {
        $path = str_replace('\\', '/', trim($path));
        $parts = [];

        foreach (explode('/', $path) as $part) {
            if ($part === '' || $part === '.') {
                continue;
            }

            if ($part === '..') {
                throw new FormToolException('File path cannot contain parent directory segments.');
            }

            $parts[] = $part;
        }

        return implode('/', $parts);
    }

    private static function resetCrop(): void
    {
        self::$cropWidth = null;
        self::$cropHeight = null;
        self::$cropPosition = 'center';
    }
}
