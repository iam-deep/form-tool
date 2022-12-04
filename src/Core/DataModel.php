<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Models\BaseModel;
use Biswadeep\FormTool\Support\Random;
use Closure;

class DataModel
{
    protected ?string $tableName = null;
    protected ?string $primaryId = null;
    protected ?string $token = null;
    protected ?string $foreignKey = null;

    protected ?string $primaryCol = null;

    protected ?string $orderByCol = null;
    protected string $orderByDirection = 'asc';

    protected bool $isToken = false;
    protected bool $isSoftDelete = true;

    protected Crud $crud;
    protected $model = '';

    private ?string $lastToken = null;

    public function __construct($model = null)
    {
        if ($model) {
            $this->model = $model;
            if (! \is_subclass_of($this->model, BaseModel::class)) {
                throw new \Exception($this->model.' should extend '.BaseModel::class);
            }
            if (! isset($this->model::$tableName) || ! $this->model::$tableName) {
                throw new \Exception('$tableName not set or not declared as public at ['.$this->model.']');
            }

            // Let's copy the configs
            $this->tableName = $this->model::$tableName;
            $this->primaryId = $this->model::$primaryId;
            $this->token = $this->model::$token;
            $this->foreignKey = $this->model::$foreignKey;
            $this->primaryCol = $this->model::$primaryCol;
            $this->orderByCol = $this->model::$orderByCol;
            $this->orderByDirection = $this->model::$orderByDirection;
        } else {
            $defaultModelClass = \config('form-tool.defaultModel');
            if ($defaultModelClass) {
                $this->model = $defaultModelClass;
            } else {
                $this->model = BaseModel::class;
            }
        }
    }

    //region Options

    public function db($tableName, $primaryId = 'id', $token = '', $orderByCol = '', $foreignKey = '')
    {
        $this->tableName = \trim($tableName);
        $this->primaryId = \trim($primaryId);
        $this->token = \trim($token);
        $this->orderByCol = \trim($orderByCol);
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

    public function token(string $column = 'token')
    {
        $this->isToken = true;
        $this->token = \trim($column);

        return $this;
    }

    public function orderBy(string $column, string $direction = 'desc')
    {
        $this->orderByCol = \trim($column);

        return $this;
    }

    public function foreignKey(string $column)
    {
        $this->foreignKey = \trim($column);

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

    public function getTokenCol()
    {
        return $this->token ?: $this->model::$token;
    }

    public function getLastToken()
    {
        return $this->lastToken;
    }

    public function getOrderBy()
    {
        return $this->orderByCol ?: $this->model::$orderByCol;
    }

    public function getForeignKey()
    {
        return $this->foreignKey ?: $this->model::$foreignKey;
    }

    public function softDelete(bool $enable = true)
    {
        $this->isSoftDelete = $enable;
    }

    public function setCrud(Crud $crud)
    {
        $this->crud = $crud;
    }

    //endregion

    public function getAll($where = null, ?string $orderByCol = null, string $direction = 'desc')
    {
        $this->orderByCol = $orderByCol;
        $this->orderByDirection = $direction;

        return $this->setup()::getAll($where);
    }

    public function getOne($id, $isToken = null)
    {
        return $this->setup()::getOne($id, $isToken ?? $this->isToken);
    }

    public function search($searchTerm, $fields, $where = null, $orderByCol = null, string $direction = 'desc')
    {
        $this->orderByCol = $orderByCol;
        $this->orderByDirection = $direction;

        return $this->setup()::search($searchTerm, $fields, $where);
    }

    public function getWhereOne($where = null)
    {
        return $this->setup()::getWhereOne($where);
    }

    public function getWhere($where = null, ?string $orderByCol = null, string $direction = 'asc')
    {
        return $this->setup()::getWhere($where, $orderByCol, $direction);
    }

    public function add($data)
    {
        if ($this->isToken) {
            $data[$this->token] = $this->lastToken = Random::unique($this);
        }

        $metaColumns = \config('form-tool.table_meta_columns');
        $createdBy = ($metaColumns['createdBy'] ?? 'createdBy') ?: 'createdBy';
        $createdAt = ($metaColumns['createdAt'] ?? 'createdAt') ?: 'createdAt';

        $data[$createdBy] = Auth::user() ? Auth::user()->userId : 0;
        $data[$createdAt] = \date('Y-m-d H:i:s');

        return $this->setup()::add($data);
    }

    public function addMany($data)
    {
        $this->setup()::addMany($data);
    }

    public function updateOne($id, $data, $isToken = null)
    {
        $metaColumns = \config('form-tool.table_meta_columns');
        $updatedBy = ($metaColumns['updatedBy'] ?? 'updatedBy') ?: 'updatedBy';
        $updatedAt = ($metaColumns['updatedAt'] ?? 'updatedAt') ?: 'updatedAt';

        if ($this->crud->isDefaultFormat()) {
            $data[$updatedBy] = Auth::user() ? Auth::user()->userId : 0;
            $data[$updatedAt] = \date('Y-m-d H:i:s');
        }

        return $this->setup()::updateOne($id, $data, $isToken ?? $this->isToken);
    }

    public function updateDelete($id)
    {
        $metaColumns = \config('form-tool.table_meta_columns');
        $deletedBy = ($metaColumns['deletedBy'] ?? 'deletedBy') ?: 'deletedBy';
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        $data = [];
        $data[$deletedBy] = Auth::user() ? Auth::user()->userId : 0;
        $data[$deletedAt] = \date('Y-m-d H:i:s');

        return $this->setup()::updateOne($id, $data, $isToken ?? $this->isToken);
    }

    public function restore($id)
    {
        $metaColumns = \config('form-tool.table_meta_columns');
        $deletedBy = ($metaColumns['deletedBy'] ?? 'deletedBy') ?: 'deletedBy';
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        $data = [];
        $data[$deletedBy] = null;
        $data[$deletedAt] = null;

        return $this->setup()::updateOne($id, $data, $isToken ?? $this->isToken);
    }

    public function deleteOne($id, $isToken = null)
    {
        return $this->setup()::deleteOne($id, $isToken ?? $this->isToken);
    }

    public function destroyWhere($where = null)
    {
        return $this->setup()::destroyWhere($where);
    }

    public function countWhere($where = null)
    {
        return $this->setup()::countWhere($where);
    }

    private function setup()
    {
        $this->model::$tableName = $this->tableName;
        $this->model::$primaryId = $this->primaryId;
        $this->model::$token = $this->token;
        $this->model::$foreignKey = $this->foreignKey;

        $this->model::$primaryCol = $this->primaryCol;

        $this->model::$orderByCol = $this->orderByCol;
        $this->model::$orderByDirection = $this->orderByDirection;

        $this->model::setSoftDelete($this->isSoftDelete);

        return $this->model;
    }
}
