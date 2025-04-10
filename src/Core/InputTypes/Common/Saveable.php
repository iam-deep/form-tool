<?php

namespace Deep\FormTool\Core\InputTypes\Common;

use Deep\FormTool\Core\InputTypes\BaseInputType;

trait Saveable
{
    protected $save = null;

    public function saveAt(string $tableName, string $id = 'id', ?string $refId = null): BaseInputType
    {
        if (! $this->isMultiple) {
            throw new \Exception(sprintf('Field "%s" should be multiple to apply saveAt method!', $this->dbField));
        }

        $this->save = (object) [
            'table' => $tableName,
            'id' => $id,
            'refId' => $refId,
        ];

        return $this;
    }

    public function isSaveAt(): bool
    {
        return ! is_null($this->save);
    }

    public function getSaveAt(): ?object
    {
        if (! $this->save) {
            return null;
        }

        $this->save->table = trim($this->save->table);
        $this->save->id = trim($this->save->id);
        $this->save->refId = trim($this->save->refId ?? $this->bluePrint->getForm()->getModel()->getPrimaryId());

        return $this->save;
    }
}
