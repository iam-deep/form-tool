<?php

use Biswadeep\FormTool\Core\Doc;
use Biswadeep\FormTool\Core\Auth;

if (! function_exists('getHTMLForm')) {
    function getHTMLForm(string $name = null)
    {
        return Doc::getHTMLForm($name);
    }
}

if (! function_exists('getTableContent')) {
    function getTableContent(string $name = null)
    {
        return Doc::getTableContent($name);
    }
}

if (! function_exists('getTablePagination')) {
    function getTablePagination(string $name = null)
    {
        return Doc::getTablePagination($name);
    }
}

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

if (! function_exists('getAllCss')) {
    function getAllCss()
    {
        return Doc::getCssLinks().Doc::getCss();
    }
}

if (! function_exists('getAllJs')) {
    function getAllJs()
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

if (! function_exists('ftAuth')) {
    function ftAuth()
    {
        return Auth::class;
    }
}

if (! function_exists('isSuccess')) {
    function isSuccess($response)
    {
        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            if (session()->has('success')) {
                return true;
            }
        }

        return false;
    }
}