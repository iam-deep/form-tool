<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\URL;

class Doc
{
    private static $crudList = null;
    private static $defaultCrudName = 'default';

    private static $cssLink = [];
    private static $jsLink = [];
    private static $css = [];
    private static $js = [];

    private function __construct()
    {
    }

    public static function create(object $resource, $model, Closure $callback, string $name = null)
    {
        if (! isset($resource->title)) {
            throw new \Exception('$title not set or not declared as public at ['.\get_class($resource).']');
        }
        if (! isset($resource->route)) {
            throw new \Exception('$route not set or not declared as public at ['.\get_class($resource).']!');
        }

        if (! self::$crudList) {
            self::$crudList = new \stdClass();
        }

        $name = $name ?: self::$defaultCrudName;

        $crud = new Crud();
        self::$crudList->{$name} = $crud;

        $crud->make($resource, $model, $callback, $name);

        return $crud;
    }

    public static function modify(Closure $callback, string $name = null)
    {
        $name = $name ?: self::$defaultCrudName;
        if (! isset(self::$crudList->{$name})) {
            throw new \Exception('Crud not found! You need to create it before modify.');
        }

        $crud = self::$crudList->{$name};
        $crud->modify($callback);

        return $crud;
    }

    public static function getCrud()
    {
        return self::$crudList->{self::$defaultCrudName} ?? null;
    }

    public static function getCruds()
    {
        return self::$crudList;
    }

    //region Css&Js

    public static function addCssLink($link)
    {
        $link = \trim($link);
        if (! \in_array($link, self::$cssLink)) {
            self::$cssLink[] = $link;
        }
    }

    public static function addJsLink($link)
    {
        $link = \trim($link);
        if (! \in_array($link, self::$jsLink)) {
            self::$jsLink[] = $link;
        }
    }

    // Pass a unique key for each script so that we don't add duplicate $css
    public static function addCss($css, $key = '')
    {
        if ($key) {
            self::$css[$key] = $css;
        } else {
            self::$css[] = $css;
        }
    }

    // Pass a unique key for each script so that we don't add duplicate $scripts
    public static function addJs($script, $key = '')
    {
        if ($key) {
            self::$js[$key] = $script;
        } else {
            self::$js[] = $script;
        }
    }

    public static function getCssLinks()
    {
        $links = [];
        foreach (self::$cssLink as $link) {
            if ($link) {
                if (false !== \strpos($link, '//')) {
                    $links[] = '<link href="'.$link.'" rel="stylesheet" type="text/css" />';
                } else {
                    $links[] = '<link href="'.URL::asset($link).'" rel="stylesheet" type="text/css" />';
                }
            }
        }

        return \implode("\n", $links);
    }

    public static function getJsLinks()
    {
        $links = [];
        foreach (self::$jsLink as $link) {
            if ($link) {
                if (false !== \strpos($link, '//')) {
                    $links[] = '<script src="'.$link.'"></script>';
                } else {
                    $links[] = '<script src="'.URL::asset($link).'"></script>';
                }
            }
        }

        return \implode("\n", $links);
    }

    public static function getCss()
    {
        if (! self::$css) {
            return '';
        }

        return '<style>'.\implode("\n", self::$css).'</style>';
    }

    public static function getJs()
    {
        if (! self::$js) {
            return '';
        }

        return '<script>'.\implode("\n", self::$js).'</script>';
    }

    //endregion

    private function getCurdByName(string $name = null)
    {
        $name = $name ?: self::$defaultCrudName;

        if (isset(self::$crudList->{$name})) {
            return self::$crudList->{$name};
        }

        return null;
    }
}
