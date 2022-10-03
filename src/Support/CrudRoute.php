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
}
