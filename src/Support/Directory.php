<?php

namespace Biswadeep\FormTool\Support;

class Directory
{
    public static function create($path)
    {
        if (\file_exists($path)) {
            if (! \is_writable($path)) {
                throw new \InvalidArgumentException('"'.$path.'" is not writable!');
            }

            return;
        }

        try {
            \mkdir($path, 0777, true);
            self::restrictDirectoryAccess($path);
        } catch (\Exception $e) {
            throw new \Exception('Failed to create directory: '.$path.'. '.$e->getMessage());
        }
    }

    public static function restrictDirectoryAccess(string $path)
    {
        $dirs = array_filter(\explode('/', $path));

        $parentPath = '';
        while (\count($dirs)) {
            $currentDir = array_shift($dirs);

            $indexFile = $parentPath.$currentDir.'/index.html';

            if (! \file_exists($indexFile)) {
                try {
                    $handle = \fopen($indexFile, 'w');

                    $fileData = '<!DOCTYPE html><html><head><title>403 Forbidden</title></head>
                        <body><p>Directory access is forbidden.</p></body></html>';
                    \fwrite($handle, $fileData);
                    \fclose($handle);
                } catch (\Exception $e) {
                    // TODO: Log
                }
            }

            /* It can be used to create index file for all the sub directories under parent directory
            foreach (glob($currentDir . '/*') as $dir) {
                if (is_dir($dir))
                    $dirs[] = $dir;
            }*/

            $parentPath .= $currentDir.'/';
        }
    }
}
