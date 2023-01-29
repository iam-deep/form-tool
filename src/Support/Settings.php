<?php

namespace Deep\FormTool\Support;

class Settings
{
    public static $settings = null;

    protected static $default = [
        //
    ];

    public static function init()
    {
        if (\app()->has('settings')) {
            self::$settings = \app('settings');
        }
    }

    public static function get(string $key, $default = null)
    {
        if (isset(self::$settings->{$key})) {
            return self::$settings->{$key};
        }

        if ($default) {
            return $default;
        }

        return self::$default[$key] ?? null;
    }
}
