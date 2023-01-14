<?php

use Biswadeep\FormTool\Core\Auth;
use Biswadeep\FormTool\Core\Doc;
use Biswadeep\FormTool\Core\Guard;

if (! function_exists('addCssLink')) {
    function addCssLink($link)
    {
        Doc::addCssLink($link);
    }
}

if (! function_exists('addJsLink')) {
    function addJsLink($link)
    {
        Doc::addJsLink($link);
    }
}

if (! function_exists('addJs')) {
    function addJs($script)
    {
        Doc::addJs($script);
    }
}

if (! function_exists('addCss')) {
    function addCss($css)
    {
        Doc::addCss($css);
    }
}

if (! function_exists('getCssLinks')) {
    function getCssLinks()
    {
        return Doc::getCssLinks();
    }
}

if (! function_exists('getJsLinks')) {
    function getJsLinks()
    {
        return Doc::getJsLinks();
    }
}

if (! function_exists('getCss')) {
    function getCss()
    {
        return Doc::getCss();
    }
}

if (! function_exists('getJs')) {
    function getJs()
    {
        return Doc::getJs();
    }
}

// TODO: Need to change this function name as per Package name
if (! function_exists('getFormCss')) {
    function getFormCss()
    {
        return Doc::getCssLinks().Doc::getCss();
    }
}

// TODO: Need to change this function name as per Package name
if (! function_exists('getFormJs')) {
    function getFormJs()
    {
        return Doc::getJsLinks().Doc::getJs();
    }
}

if (! function_exists('decodeHTML')) {
    function decodeHTML($data)
    {
        return (new Biswadeep\FormTool\Core\InputTypes\EditorType())->decodeHTML($data);
    }
}

if (! function_exists('encryptText')) {
    function encryptText($value)
    {
        return (new Biswadeep\FormTool\Core\InputTypes\TextType())->encrypt()->doEncrypt($value);
    }
}

if (! function_exists('decryptText')) {
    function decryptText($value)
    {
        return (new Biswadeep\FormTool\Core\InputTypes\TextType())->encrypt()->doDecrypt($value);
    }
}

if (! function_exists('imageThumb')) {
    function imageThumb($value)
    {
        return (new Biswadeep\FormTool\Core\InputTypes\ImageType())->getNiceValue($value);
    }
}

if (! function_exists('isSuccess')) {
    function isSuccess($response)
    {
        if ($response instanceof \Illuminate\Http\RedirectResponse && session()->has('success')) {
            return true;
        }

        return false;
    }
}

if (! function_exists('getSidemenu')) {
    function getSidemenu()
    {
        return \Biswadeep\FormTool\Support\Menu::generate();
    }
}

if (! function_exists('guard')) {
    function guard()
    {
        return Guard::class;
    }
}

if (! function_exists('removeSlash')) {
    function removeSlash($path)
    {
        return \str_replace(['/', '\\'], '', $path);
    }
}

if (! function_exists('isNullOrEmpty')) {
    function isNullOrEmpty($value)
    {
        return $value === null || trim($value) === '';
    }
}

if (! function_exists('niceDateTime')) {
    function niceDateTime($dateTime)
    {
        return \Biswadeep\FormTool\Support\DTConverter::niceDateTime($dateTime, true);
    }
}

if (! function_exists('niceDate')) {
    function niceDate($date)
    {
        return \Biswadeep\FormTool\Support\DTConverter::niceDate($date, true);
    }
}

if (! function_exists('niceTime')) {
    function niceTime($dateTime)
    {
        return \Biswadeep\FormTool\Support\DTConverter::niceTime($dateTime, true);
    }
}
