<?php

namespace Biswadeep\FormTool\Support;

use Illuminate\Support\Facades\Route;

class CrudRoute
{
    public static function resource($route, $class)
    {
        Route::get($route.'/search', [$class, 'search'])->name($route.'.search');
        Route::resource($route, $class);
    }
}