<?php

namespace Biswadeep\FormTool\Core;

use Illuminate\Database\Eloquent\Model;

class Auth
{
    protected static $config = null;
    protected static $user = null;

    public static function user()
    {
        if (self::$user) {
            return self::$user;
        }

        self::getUser();

        return self::$user;
    }

    public static function id()
    {
        self::getUser();

        if (! self::$config['isCustomAuth']) {
            return \auth()->id();
        }

        if (isset(self::$config['id'])) {
            return self::$config['id'];
        }

        $model = new self::$config['userModel']();
        self::$config['id'] = self::$user->{$model->getKeyName()} ?? 0;

        return self::$config['id'];
    }

    private static function getUser()
    {
        if (self::$config) {
            return;
        }

        $config = self::$config = \config('form-tool.auth', ['isCustomAuth' => false]);

        if (! isset($config['userModel'])) {
            throw new \Exception('$auth[\'userModel\'] is not defined in form-tool config');
        }

        if (! \is_subclass_of($config['userModel'], Model::class)) {
            throw new \Exception(\sprintf('"%s" is not an instance of %s', $config['userModel'], Model::class));
        }

        if (! $config['isCustomAuth']) {
            self::$user = \auth()->user();

            return;
        }

        if (! \method_exists($config['userModel'], 'user')) {
            throw new \Exception(\sprintf('static "%s()" method not found in %s', 'user', $config['userModel']));
        }

        self::$user = $config['userModel']::user();
        if (! self::$user) {
            throw new \Exception(\sprintf('User data not found from %s::user()', $config['userModel']));
        }
    }
}
