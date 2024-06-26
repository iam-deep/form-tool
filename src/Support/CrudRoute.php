<?php

namespace Deep\FormTool\Support;

use Illuminate\Support\Facades\Route;

class CrudRoute
{
    public static function resource($route, $class, array $options = [])
    {
        $name = str_replace('/', '.', $route);

        Route::get($route.'/search', [$class, 'search'])->name($name.'.search');
        Route::post($route.'/bulk-action', [$class, 'bulkAction'])->name($name.'.bulk-action');
        Route::post($route.'/get-options', [$class, 'getOptions'])->name($name.'.get-options');

        return Route::resource($route, $class, $options)->parameters([$route => 'id']);
    }

    public static function importExport($route, $class)
    {
        $name = str_replace('/', '.', $route);

        Route::get($route.'/import', [$class, 'import'])->name($name.'.import');
        Route::post($route.'/import', [$class, 'importStore'])->name($name.'.import-store');
        Route::get($route.'/export', [$class, 'export'])->name($name.'.export');
        Route::get($route.'/sample', [$class, 'sample'])->name($name.'.sample');
    }

    public static function indexAndStore($route, $class)
    {
        $name = str_replace('/', '.', $route);

        Route::get($route, [$class, 'index'])->name($name);
        Route::post($route, [$class, 'store'])->name($name.'.store');
    }

    public static function indexAndUpdate($route, $class, $id = null)
    {
        $name = str_replace('/', '.', $route);

        Route::get($route, [$class, 'index'])->name($name);
        Route::put($route.$id, [$class, 'update'])->name($name.'.update');
    }

    public static function indexAndDestroy($route, $class, $id = null)
    {
        $name = str_replace('/', '.', $route);

        Route::get($route, [$class, 'index'])->name($name);
        Route::destroy($route.$id, [$class, 'destroy'])->name($name.'.destroy');
    }

    public static function apiResource($route, $class, array $options = [])
    {
        $name = str_replace('/', '.', $route);

        Route::get($route.'/search', [$class, 'search'])->name($name.'.search');
        Route::post($route.'/get-options', [$class, 'getOptions'])->name($name.'.get-options');

        return Route::apiResource($route, $class, $options)->parameters([$route => 'id']);
    }
}
