<?php 

use Biswadeep\FormTool\Http\Controllers\AccountController;

Route::get('login', [AccountController::class, 'index']);