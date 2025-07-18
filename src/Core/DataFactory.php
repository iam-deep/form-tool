<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Exceptions\FormToolException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class DataFactory
{
    private static $rootDir = 'form-tool';

    private function getSegments()
    {
        $route = Route::currentRouteName();

        $filename = $action = '';
        $segments = \explode('.', $route);
        if (\count($segments) > 1) {
            $filename = $segments[0];
            $action = $segments[1];
        }

        if (! $filename || ! $action) {
            throw new FormToolException('route name not found!');
        }

        $segment = new \stdClass();
        $segment->path = self::$rootDir.'/'.$filename.'.txt';
        $segment->action = $action;

        return $segment;
    }

    public static function put($bluePrint)
    {
        $segment = self::getSegments();

        $type = 'update';
        if ($segment->action == 'create') {
            $type = 'store';
        }

        $append = $bluePrint->toObj($type);

        $data = new \stdClass();
        if (Storage::has($segment->path)) {
            $data = \json_decode(Storage::get($segment->path));
        }

        $data->{$type} = $append;

        Storage::put($segment->path, \json_encode($data));

        self::get();
    }

    public static function get()
    {
        $segment = self::getSegments();

        $type = 'update';
        if ($segment->action == 'create') {
            $type = 'store';
        }

        $data = new \stdClass();
        if (Storage::has($segment->path)) {
            $data = \json_decode(Storage::get($segment->path));
        }

        return $data->{$type} ?? null;
    }
}
