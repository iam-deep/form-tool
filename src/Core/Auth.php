<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class Auth
{
    protected static $user = null;

    public static function setUser($user = null)
    {
        self::$user = $user ?? Session::get('user');
    }

    public static function user()
    {
        return self::$user;
    }

    public static function fetchUser()
    {
        self::$user = DB::table('users')->where('userId', self::$user->userId)->first();

        return self::$user;
    }
}
