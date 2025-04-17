<?php

namespace Deep\FormTool\Models;

use Deep\FormTool\Dtos\MultipleTableDto;
use Illuminate\Support\Facades\DB;

class MultipleTableModel
{
    private MultipleTableDto $model;
    private $foreignCol = null;
    private $primaryCol = null;

    private static ?MultipleTableModel $instance = null;

    public static function init(MultipleTableDto $modelDto): MultipleTableModel
    {
        if ($modelDto == null) {
            throw new \InvalidArgumentException('Table model cannot be null.');
        }

        $instance = self::$instance = new self();

        $instance->model = $modelDto;

        if ($modelDto->modelType == 'table') {
            $instance->foreignCol = $modelDto->foreignCol;
            $instance->primaryCol = $modelDto->primaryCol;
        } else {
            $dbClassModel = $modelDto->className;

            // This validation is already done in MultipleTableDto, will be removed after refactoring
            // if (! $dbClassModel::$foreignKey || ! $dbClassModel::$primaryId) {
            //     throw new \InvalidArgumentException('$foreignKey or $primaryId property not defined at '.$dbClassModel);
            // }

            $instance->foreignCol = $dbClassModel::$foreignKey;
            $instance->primaryCol = $dbClassModel::$primaryId;
        }

        return $instance;
    }

    public function getAll($id): \Illuminate\Support\Collection
    {
        if ($this->model->modelType == 'table') {
            $query = DB::table($this->model->tableName)->where([$this->model->foreignCol => $id]);
            if ($this->model->where) {
                $closure = $this->model->where;
                $closure($query);
            }
            return $query->orderBy($this->model->primaryCol, 'asc')->get();
        }

        $foreignKey = $this->model->className::$foreignKey;

        $where[] = [$foreignKey => $id];
        if ($this->model->where) {
            $where[] = $this->model->where;
        }

        if ($this->model->orderableColumn) {
            $this->model->className::$orderByCol = $this->model->orderableColumn;
        }

        return $this->model->className::getWhere($where);
    }

    public function add($id, $insert): ?int
    {
        $where = [$this->foreignCol => $id];
        if ($this->model->modelType == 'table') {
            $query = DB::table($this->model->tableName)->where($where);
            if ($this->model->where) {
                $closure = $this->model->where;
                $closure($query);
            }
            $query->delete();

            if (\count($insert)) {
                return DB::table($this->model->tableName)->insert($insert);
            }
        } else {
            $this->model->className::deleteWhere($where);
            if (\count($insert)) {
                return $this->model->className::addMany($insert);
            }
        }

        return null;
    }

    public function destroy($id): ?int
    {
        if ($this->model->modelType == 'table') {
            return DB::table($this->model->tableName)->where([$this->model->foreignCol => $id])->delete();
        }

        return $this->model->className::deleteWhere([$this->model->foreignCol => $id]);
    }

    public function getForeignCol(): string
    {
        return $this->foreignCol;
    }

    public function getPrimaryCol(): string
    {
        return $this->primaryCol;
    }

    public function getInstance(): MultipleTableModel
    {
        return self::$instance;
    }
}
