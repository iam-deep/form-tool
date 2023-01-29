<?php

Route::group(
    [
        'prefix' => config('form-tool.adminURL'),
        'middleware' => config('form-tool.auth.middleware', ['web', 'auth']),
    ],
    function () {
        Route::post('form-tool/editor-upload', [Deep\FormTool\Core\InputTypes\EditorType::class, 'uploadImage']);
    }
);
