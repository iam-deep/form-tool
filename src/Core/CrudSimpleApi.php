<?php

namespace Deep\FormTool\Core;

class CrudSimpleApi extends Crud
{
    public function index()
    {
        $data = $this->model->getAll();

        $data = [
            'current_page' => $data->currentPage(),
            'last_page' => $data->perPage(),
            'from' => $data->firstItem() ?? 0,
            'to' => $data->lastItem() ?? 0,
            'total' => $data->total(),
            'per_page' => $data->perPage(),
            'data' => $data->items(),
        ];

        return $data;
    }

    public function list($callback = null)
    {
    }
}
