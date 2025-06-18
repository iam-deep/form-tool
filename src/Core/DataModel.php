<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Core\InputTypes\Common\ISaveable;
use Deep\FormTool\Models\BaseModel;
use Deep\FormTool\Support\Random;
use Illuminate\Support\Facades\DB;

class DataModel
{
    protected ?string $tableName = null;
    protected ?string $primaryId = null;
    protected ?string $token = null;
    protected ?string $foreignKey = null;
    protected ?string $alias = null;

    protected ?string $orderByCol = null;
    protected string $orderByDirection = 'asc';

    protected bool $isToken = false;
    protected bool $isSoftDelete = true;

    protected Crud $crud;

    /** @var class-string<BaseModel>|null */
    protected $model = null;

    private ?string $lastToken = null;

    public function __construct($model = null)
    {
        if ($model) {
            $this->model = $model;
            if (! isset($this->model::$tableName) || ! $this->model::$tableName) {
                throw new \InvalidArgumentException(sprintf(
                    '$tableName not set or not declared as public at [%s]',
                    $this->model
                ));
            }

            // Let's copy the configs
            $this->tableName = $this->model::$tableName;
            $this->primaryId = $this->model::$primaryId;
            $this->token = $this->model::$token;
            $this->foreignKey = $this->model::$foreignKey;
            $this->alias = $this->model::$alias ?: $this->tableName;
            $this->orderByCol = $this->model::$orderByCol;
            $this->orderByDirection = $this->model::$orderByDirection;
        } else {
            $defaultModelClass = \config('form-tool.defaultModel', BaseModel::class);
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
        $this->orderByDirection = \trim($direction);

        return $this;
    }

    public function foreignKey(string $column)
    {
        $this->foreignKey = \trim($column);

        return $this;
    }

    public function alias(string $alias)
    {
        $this->alias = \trim($alias);

        return $this;
    }

    //endregion

    //region GettersAndSetters

    public function getTableName()
    {
        return $this->tableName;
    }

    public function getPrimaryId()
    {
        return $this->primaryId;
    }

    public function isToken()
    {
        return $this->isToken;
    }

    public function getTokenCol()
    {
        return $this->token;
    }

    public function getLastToken()
    {
        return $this->lastToken;
    }

    public function getOrderBy()
    {
        return $this->orderByCol ?: $this->primaryId;
    }

    public function getForeignKey()
    {
        return $this->foreignKey;
    }

    public function getAlias()
    {
        return $this->alias;
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

        $data = $this->setup()::getAll($where);

        if ($data->total()) {
            $inputs = $this->crud->getBluePrint()->getInputList();

            $fields = $this->crud->getTable()->getFields()->toArray();
            foreach ($data as &$dataRow) {
                foreach ($inputs as $input) {
                    if ($input instanceof ISaveable && $input->isSaveAt() && in_array($input->getDbField(), $fields)) {
                        $saveAt = $input->getSaveAt();

                        $dataRow->{$input->getDbField()} = [];
                        $result = DB::table($saveAt->table)->where($saveAt->refId, $dataRow->{$this->primaryId})->get();
                        foreach ($result as $row) {
                            $dataRow->{$input->getDbField()}[] = $row->{$input->getDbField()};
                        }
                    }
                }
            }
        }

        return $data;
    }

    public function getOne($id, $isToken = null)
    {
        $data = $this->setup()::getOne($id, $isToken ?? $this->isToken);

        if ($data) {
            $inputs = $this->crud->getBluePrint()->getInputList();

            if ($this->isToken()) {
                $primaryCol = $this->primaryId;
                $id = $data->{$primaryCol};
            }

            foreach ($inputs as $input) {
                if ($input instanceof ISaveable && $input->isSaveAt()) {
                    $saveAt = $input->getSaveAt();

                    $data->{$input->getDbField()} = [];
                    $result = DB::table($saveAt->table)->where($saveAt->refId, $id)->get();
                    foreach ($result as $row) {
                        $data->{$input->getDbField()}[] = $row->{$input->getDbField()};
                    }
                }
            }
        }

        return $data;
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

        $data[$createdBy] = Auth::id();
        $data[$createdAt] = \date('Y-m-d H:i:s');

        return $this->setup()::add($data);
    }

    public function addMany($data)
    {
        // $metaColumns = \config('form-tool.table_meta_columns');
        // $createdBy = ($metaColumns['createdBy'] ?? 'createdBy') ?: 'createdBy';
        // $createdAt = ($metaColumns['createdAt'] ?? 'createdAt') ?: 'createdAt';

        // $id = Auth::id();

        foreach ($data as &$row) {
            if ($this->isToken) {
                $row[$this->token] = $this->lastToken = Random::unique($this);
            }

            // This is affecting settings (form group)
            // $row[$createdBy] = $id;
            // $row[$createdAt] = \date('Y-m-d H:i:s');
        }

        $this->setup()::addMany($data);
    }

    public function updateOne($id, $data, $isToken = null)
    {
        $metaColumns = \config('form-tool.table_meta_columns');
        $updatedBy = ($metaColumns['updatedBy'] ?? 'updatedBy') ?: 'updatedBy';
        $updatedAt = ($metaColumns['updatedAt'] ?? 'updatedAt') ?: 'updatedAt';

        if ($this->crud->isDefaultFormat()) {
            $data[$updatedBy] = Auth::id();
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
        $data[$deletedBy] = Auth::id();
        $data[$deletedAt] = \date('Y-m-d H:i:s');

        return $this->setup()::updateOne($id, $data);
    }

    public function restore($id)
    {
        $metaColumns = \config('form-tool.table_meta_columns');
        $deletedBy = ($metaColumns['deletedBy'] ?? 'deletedBy') ?: 'deletedBy';
        $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

        $data = [];
        $data[$deletedBy] = null;
        $data[$deletedAt] = null;

        return $this->setup()::updateOne($id, $data);
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

    private function setup(): ?\Deep\FormTool\Models\BaseModel
    {
        $this->model::$tableName = $this->tableName;
        $this->model::$primaryId = $this->primaryId;
        $this->model::$token = $this->token;
        $this->model::$foreignKey = $this->foreignKey;
        $this->model::$alias = $this->alias;

        $this->model::$orderByCol = $this->orderByCol;
        $this->model::$orderByDirection = $this->orderByDirection;

        $this->model::setSoftDelete($this->isSoftDelete);

        return $this->model;
    }
}
