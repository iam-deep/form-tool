<?php

use Biswadeep\FormTool\Http\Libraries\Crud;

if (! function_exists('getForm')) {
    function getForm()
    {
        return Crud::getForm();
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