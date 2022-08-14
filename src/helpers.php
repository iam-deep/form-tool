<?php

use Biswadeep\FormTool\Http\Libraries\Crud;

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