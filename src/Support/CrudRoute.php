<?php

namespace Biswadeep\FormTool\Support;

use Biswadeep\FormTool\Http\Middleware\AdminCheckLoggedIn;
use Illuminate\Support\Facades\Route;

class CrudRoute
{
    public static function resource($route, $class)
    {
        Route::get($route.'/search', [$class, 'search'])->name($route.'.search');
        Route::post($route.'/bulk-action', [$class, 'bulkAction'])->name($route.'.bulk-action');
        Route::post($route.'/get-options', [$class, 'getOptions'])->name($route.'.get-options');
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

    public static function authRoute()
    {
        if (! config('form-tool.isAuth')) {
            return;
        }

        Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', AdminCheckLoggedIn::class, 'throttle:login']], function () {
            Route::post('login', [\Biswadeep\FormTool\Http\Controllers\AuthController::class, 'loginPost']);
        });

        Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', AdminCheckLoggedIn::class]], function () {
            Route::get('/', [\Biswadeep\FormTool\Http\Controllers\AuthController::class, 'index']);

            $prefix = trim(config('form-tool.adminURL'), '/');
            $prefix && $prefix = $prefix.'.';

            Route::get('login', [\Biswadeep\FormTool\Http\Controllers\AuthController::class, 'login'])->name($prefix.'login');
            Route::get('logout', [\Biswadeep\FormTool\Http\Controllers\AuthController::class, 'logout'])->withoutMiddleware([AdminCheckLoggedIn::class]);
        });
    }
}
