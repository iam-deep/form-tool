<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\URL;

class Crud
{
    private static $_resource;
    private static $_model;
    private static $_dataModel;
    private static $_form;
    private static $_table;

    private static $_cssLink = []; 
    private static $_jsLink = []; 
    private static $_css = [];
    private static $_js = [];

    public static function createModel(object $resource, string $model, Closure $callback)
    {
        self::$_resource = $resource;
        self::$_model = $model;

        self::$_dataModel = new DataModel();
        $callback(self::$_dataModel);

        self::$_form = new Form(self::$_resource, self::$_model, self::$_dataModel);
        $response = self::$_form->init();

        if ($response instanceof \Illuminate\Http\RedirectResponse) {
            return $response->send();
        }

        self::$_table = new Table(self::$_resource, self::$_model, self::$_dataModel);
    }

    public static function edit($id)
    {
        self::$_form->edit($id);
    }

    public static function createTable(Closure $callback)
    {
        $tableField = new TableField(self::$_table);
        $callback($tableField);

        return self::$_table->setTableField($tableField);
    }

    public static function getTableContent()
    {
        return self::$_table->getContent();
    }

    public static function getTablePagination()
    {
        return self::$_table->getPagination();
    }

    public static function getForm()
    {
        return self::$_form;
    }

    public static function getHTMLForm()
    {
        return self::$_form->getForm();
    }

    public static function addCssLink($link)
    {
        $link = \trim($link);
        if (! \in_array($link, self::$_cssLink)) {
            self::$_cssLink[] = $link;
        }
    }

    public static function addJsLink($link)
    {
        $link = \trim($link);
        if (! \in_array($link, self::$_jsLink)) {
            self::$_jsLink[] = $link;
        }
    }

    // Pass a unique key for each script so that we don't add duplicate $css
    public static function addCss($css, $key = '')
    {
        if ($key)
            self::$_css[$key] = $css;
        else
            self::$_css[] = $css;
    }

    // Pass a unique key for each script so that we don't add duplicate $scripts
    public static function addJs($script, $key = '')
    {
        if ($key)
            self::$_js[$key] = $script;
        else
            self::$_js[] = $script;
    }

    public static function getCssLinks()
    {
        $links = [];
        foreach (self::$_cssLink as $link) {
            if ($link)
                if (false !== \strpos($link,'//'))
                    $links[] = '<link href="'.$link.'" rel="stylesheet" type="text/css" />';
                else
                    $links[] = '<link href="'. URL::asset($link) .'" rel="stylesheet" type="text/css" />';
        }

        return \implode("\n", $links);
    }

    public static function getJsLinks()
    {
        $links = [];
        foreach (self::$_jsLink as $link) {
            if ($link)
                if (false !== \strpos($link,'//'))
                    $links[] = '<script src="'.$link.'"></script>';
                else
                    $links[] = '<script src="'. URL::asset($link) .'"></script>';
        }

        return \implode("\n", $links);
    }

    public static function getCss()
    {
        if (! self::$_css)
            return '';

        return '<style>'.\implode("\n", self::$_css).'</style>';
    }

    public static function getJs()
    {
        if (! self::$_js)
            return '';
        
        return '<script>'.\implode("\n", self::$_js).'</script>';
    }
}
