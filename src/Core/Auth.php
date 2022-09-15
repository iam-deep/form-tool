<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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

    public static function refresh()
    {
        // Let's update the current session and profile data
        $user = self::fetchUser();
        $user->adminLoginToken = Hash::make($user->password.$user->email.$_SERVER['HTTP_USER_AGENT']);

        Session::put('user', $user);
    }
}
