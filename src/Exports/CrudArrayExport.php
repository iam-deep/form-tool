<?php

namespace Deep\FormTool\Exports;

use Maatwebsite\Excel\Concerns\FromArray;

class CrudArrayExport implements FromArray
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        return $this->data;
    }
}
