<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Models\AdminModel;
use Biswadeep\FormTool\Support\Random;
use Closure;

class DataModel
{
    protected string $tableName = '';
    protected string $primaryId = '';
    protected string $token = '';
    protected string $orderBy = '';
    protected string $foreignKey = '';

    protected bool $isToken = false;
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

    //region Options

    public function db($tableName, $primaryId = 'id', $token = '', $orderBy = '', $foreignKey = '')
    {
        $this->tableName = \trim($tableName);
        $this->primaryId = \trim($primaryId);
        $this->token = \trim($token);
        $this->orderBy = \trim($orderBy);
        $this->foreignKey = \trim($foreignKey);

        if ($this->token) {
            $this->isToken = true;
        }

        return $this;
    }

    public function table(string $tableName)
    {
        $this->tableName = \trim($tableName);

        return $this;
    }

    public function id(string $primaryId)
    {
        $this->primaryId = \trim($primaryId);

        return $this;
    }

    public function token(string $tokenColumn = 'token')
    {
        $this->isToken = true;
        $this->token = \trim($tokenColumn);

        return $this;
    }

    public function orderBy(string $orderByColumn)
    {
        $this->orderBy = \trim($orderByColumn);

        return $this;
    }

    public function foreignKey(string $foreignKeyColumn)
    {
        $this->foreignKey = \trim($foreignKeyColumn);

        return $this;
    }

    //endregion

    //region GettersAndSetters

    public function getTableName()
    {
        return $this->tableName ?: $this->model::$tableName;
    }

    public function getPrimaryId()
    {
        return $this->primaryId ?: $this->model::$primaryId;
    }

    public function isToken()
    {
        return $this->isToken;
    }

    public function getToken()
    {
        return $this->token ?: $this->model::$token;
    }

    public function getOrderBy()
    {
        return $this->orderBy ?: $this->model::$orderBy;
    }

    public function getForeignKey()
    {
        return $this->foreignKey ?: $this->model::$foreignKey;
    }

    public function softDelete(bool $enable = true)
    {
        $this->isSoftDelete = $enable;
    }

    //endregion

    public function getAll($isFromTrash = false)
    {
        return $this->setup()::getAll($isFromTrash);
    }

    public function getOne($id, $isToken = null)
    {
        return $this->setup()::getOne($id, $isToken ?? $this->isToken);
    }

    public function search($searchTerm, $fields, $isFromTrash = false)
    {
        return $this->setup()::search($searchTerm, $fields, $isFromTrash);
    }

    public function getWhereOne($where = null)
    {
        return $this->setup()::getWhereOne($where);
    }

    public function getWhere($where = null)
    {
        return $this->setup()::getWhere($where);
    }

    public function add($data)
    {
        if ($this->isToken) {
            $data[$this->token] = Random::unique($this);
        }

        return $this->setup()::add($data);
    }

    public function addMany($data)
    {
        $this->setup()::addMany($data);
    }

    public function updateOne($id, $data, $isToken = null)
    {
        return $this->setup()::updateOne($id, $data, $isToken ?? $this->isToken);
    }

    public function deleteOne($id, $isToken = null)
    {
        return $this->setup()::deleteOne($id, $isToken ?? $this->isToken);
    }

    public function deleteWhere($where = null)
    {
        return $this->setup()::deleteWhere($where);
    }

    public function countWhere($where = null)
    {
        return $this->setup()::countWhere($where);
    }

    private function setup()
    {
        $this->model::$tableName = $this->tableName ?: $this->model::$tableName;
        $this->model::$primaryId = $this->primaryId ?: $this->model::$primaryId;
        $this->model::$token = $this->token ?: $this->model::$token;
        $this->model::$orderBy = $this->orderBy ?: $this->model::$orderBy;
        $this->model::$foreignKey = $this->foreignKey ?: $this->model::$foreignKey;

        $this->model::$isSoftDelete = $this->isSoftDelete;

        return $this->model;
    }
}
