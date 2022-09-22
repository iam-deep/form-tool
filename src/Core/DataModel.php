<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Models\AdminModel;
use Closure;

class DataModel
{
    protected string $tableName = '';
    protected string $primaryId = '';
    protected string $orderBy = '';
    protected string $foreignKey = '';

    protected bool $isSoftDelete = true;

    protected string $model = '';

    public function __construct($model = null)
    {
        if ($model) {
            $this->model = $model;
        } else {
            $this->model = AdminModel::class;
        }
    }

    public function db($tableName, $primaryId = 'id', $orderBy = '', $foreignKey = '')
    {
        $this->tableName = $tableName;
        $this->primaryId = $primaryId;
        $this->orderBy = $orderBy;
        $this->foreignKey = $foreignKey;

        return $this;
    }

    public function softDelete(bool $enable = true)
    {
        $this->isSoftDelete = $enable;

        return $this;
    }

    public function getTableName()
    {
        return $this->tableName ?: $this->model::$tableName;
    }

    public function getPrimaryId()
    {
        return $this->primaryId ?: $this->model::$primaryId;
    }

    public function getOrderBy()
    {
        return $this->orderBy ?: $this->model::$orderBy;
    }

    public function getForeignKey()
    {
        return $this->foreignKey ?: $this->model::$foreignKey;
    }

    public function getAll($isFromTrash = false)
    {
        return $this->setup()::getAll($isFromTrash);
    }

    public function getOne($id)
    {
        return $this->setup()::getOne($id);
    }

    public function search($searchTerm, $fields, $isFromTrash = false)
    {
        return $this->setup()::search($searchTerm, $fields, $isFromTrash);
    }

    public function getWhere($id)
    {
        return $this->setup()::getWhere($id);
    }

    public function add($data)
    {
        return $this->setup()::add($data);
    }

    public function addMany($data)
    {
        $this->setup()::addMany($data);
    }

    public function updateOne($id, $data)
    {
        return $this->setup()::updateOne($id, $data);
    }

    public function deleteOne($id)
    {
        return $this->setup()::deleteOne($id);
    }

    public function deleteWhere($where)
    {
        return $this->setup()::deleteWhere($where);
    }

    public function countWhere(Closure $where = null)
    {
        return $this->setup()::countWhere($where);
    }

    private function setup()
    {
        $this->model::setup($this->tableName, $this->primaryId, $this->orderBy, $this->foreignKey);

        return $this->model;
    }
}
