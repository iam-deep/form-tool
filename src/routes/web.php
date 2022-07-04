<?php 

use Illuminate\Routing\Route;
use Biswadeep\FormTool\Http\Controllers\AccountController;

Route::get('login', [AccountController::class, 'index']);