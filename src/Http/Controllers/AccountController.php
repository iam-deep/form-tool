<?php

namespace Biswadeep\FormTool\Http\Controllers;

use App\Http\Controllers\Controller;

class AccountController extends Controller
{
    public function index()
    {
        return view('FormTool::login');
    }
}