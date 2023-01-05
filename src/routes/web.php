<?php

Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', config('form-tool.auth.middleware', 'auth')]], function () {
    Route::post('form-tool/editor-upload', [Biswadeep\FormTool\Core\InputTypes\EditorType::class, 'uploadImage']);
});
