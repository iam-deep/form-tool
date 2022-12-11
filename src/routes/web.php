<?php

use Biswadeep\FormTool\Http\Middleware\AdminAuth;

/* Authenticated Routes */
Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', AdminAuth::class]], function () {
    Route::post('form-tool/editor-upload', [Biswadeep\FormTool\Core\InputTypes\EditorType::class, 'uploadImage']);
});
