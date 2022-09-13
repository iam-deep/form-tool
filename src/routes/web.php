<?php

use Biswadeep\FormTool\Http\Controllers\AuthController;
use Biswadeep\FormTool\Http\Middleware\AdminAuth;
use Biswadeep\FormTool\Http\Middleware\AdminCheckLoggedIn;

/** Auth Routes **/
Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', AdminCheckLoggedIn::class/*, 'throttle:login'*/]], function () {
    Route::post('login', [AuthController::class, 'loginPost']);
});

Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', AdminCheckLoggedIn::class]], function () {
    Route::get('/', [AuthController::class, 'index']);
    Route::get('login', [AuthController::class, 'login'])->name('login');
    Route::get('logout', [AuthController::class, 'logout'])->withoutMiddleware([AdminCheckLoggedIn::class]);
});

/* Authenticated Routes */
Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', AdminAuth::class]], function () {

    //Route::resource('demo-page', DemoController::class);

    Route::post('form-tool/editor-upload', [Biswadeep\FormTool\Core\InputTypes\EditorType::class, 'uploadImage']);
});
