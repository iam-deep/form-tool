<?php

use Biswadeep\FormTool\Core\Crud;

if (! function_exists('getHTMLForm')) {
    function getHTMLForm()
    {
        return Crud::getHTMLForm();
    }
}

if (! function_exists('getTableContent')) {
    function getTableContent()
    {
        return Crud::getTableContent();
    }
}

if (! function_exists('getTablePagination')) {
    function getTablePagination()
    {
        return Crud::getTablePagination();
    }
}

if (! function_exists('addCssLink')) {
    function addCssLink($link)
    {
        Crud::addCssLink($link);
    }
}

if (! function_exists('addJsLink')) {
    function addJsLink($link)
    {
        Crud::addJsLink($link);
    }
}

if (! function_exists('addJs')) {
    function addJs($script)
    {
        Crud::addJs($script);
    }
}

if (! function_exists('addCss')) {
    function addCss($css)
    {
        Crud::addCss($css);
    }
}

if (! function_exists('getCssLinks')) {
    function getCssLinks()
    {
        return Crud::getCssLinks();
    }
}

if (! function_exists('getJsLinks')) {
    function getJsLinks()
    {
        return Crud::getJsLinks();
    }
}

if (! function_exists('getCss')) {
    function getCss()
    {
        return Crud::getCss();
    }
}

if (! function_exists('getJs')) {
    function getJs()
    {
        return Crud::getJs();
    }
}

if (! function_exists('getAllCss')) {
    function getAllCss()
    {
        return Crud::getCssLinks().Crud::getCss();
    }
}

if (! function_exists('getAllJs')) {
    function getAllJs()
    {
        return Crud::getJsLinks().Crud::getJs();
    }
}

if (! function_exists('decodeHTML')) {
    function decodeHTML($data)
    {
        return (new Biswadeep\FormTool\Core\InputTypes\EditorType())->decodeHTML($data);
    }
}
