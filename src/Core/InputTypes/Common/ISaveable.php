<?php

namespace Deep\FormTool\Core\InputTypes\Common;

use Deep\FormTool\Core\InputTypes\BaseInputType;

interface ISaveable
{
    public function saveAt(string $tableName, string $id = 'id', string $refId = null): BaseInputType;

    public function isSaveAt(): bool;

    public function getSaveAt(): object|null;
}
