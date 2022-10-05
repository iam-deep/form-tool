<?php

namespace Biswadeep\FormTool\Support;

use Illuminate\Support\Facades\Route;

class CrudRoute
{
    public static function resource($route, $class)
    {
        Route::get($route.'/search', [$class, 'search'])->name($route.'.search');
        Route::post($route.'/bulk-action', [$class, 'bulkAction'])->name($route.'.bulk-action');
        Route::resource($route, $class);
    }

    public static function indexAndStore($route, $class)
    {
        Route::get($route, [$class, 'index'])->name($route);
        Route::post($route.'/create', [$class, 'store'])->name($route.'.store');
    }

    public static function indexAndUpdate($route, $class, $id = null)
    {
        Route::get($route, [$class, 'index'])->name($route);
        Route::put($route.$id, [$class, 'update'])->name($route.'.update');
    }

    public static function indexAndDestroy($route, $class, $id = null)
    {
        Route::get($route, [$class, 'index'])->name($route);
        Route::destroy($route.$id, [$class, 'destroy'])->name($route.'.destroy');
    }
}
