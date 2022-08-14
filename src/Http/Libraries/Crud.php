<?php

namespace Biswadeep\FormTool\Http\Libraries;

use Closure;

class Crud
{
    private static $_resource;
    private static $_model;
    private static $_dataModel;
    private static $_form;
    private static $_table;

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

        //return false;// $table->setTableField($a)->create();
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
}
