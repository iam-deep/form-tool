<?php

Route::group(['prefix' => config('form-tool.adminURL'), 'middleware' => ['web', 'auth']], function () {
    Route::post('form-tool/editor-upload', [Biswadeep\FormTool\Core\InputTypes\EditorType::class, 'uploadImage']);
});
