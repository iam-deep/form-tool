<?php

namespace Biswadeep\FormTool\Core;

class BulkAction
{
    public function getActions(string $group)
    {
        $actions = [
            'normal' => [
                'duplicate' => 'Duplicate',
                'delete' => 'Delete',
            ],
            'trash' => [
                'restore' => 'Restore',
                'deletePermanently' => 'Delete Permanently',
            ],
        ];

        if (isset($actions[$group])) {
            return $actions[$group];
        }

        return null;
    }

    public function delete()
    {

    }
}