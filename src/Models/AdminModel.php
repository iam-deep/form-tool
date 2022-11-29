<?php

namespace Biswadeep\FormTool\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminModel extends Model
{
    // This public variables can be changed from the child class
    public static $tableName = '';
    public static $primaryId = 'id';
    public static $token = 'token';
    public static $orderBy = null;
    public static $foreignKey = '';

    // You should not modify this variable from child class
    protected static $isSoftDelete = true;

    public static function getAll($where = null)
    {
        $query = DB::table(static::$tableName);

        self::applyWhere($query, $where);

        $request = \request();
        if (static::$orderBy) {
            $query->orderBy(static::$orderBy, $request->query('order') == 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy(static::$primaryId, 'desc');
        }

        return $query->paginate(20);
    }

    public static function getOne($id, $isToken = false)
    {
        $query = DB::table(static::$tableName);
        if (self::$isSoftDelete) {
            $metaColumns = \config('form-tool.table_meta_columns');
            $deletedAt = $metaColumns['deletedAt'] ?? 'deletedAt';

            $query->whereNull($deletedAt);
        }

        if ($isToken) {
            $query->where(static::$token, $id);
        } else {
            $query->where(static::$primaryId, $id);
        }

        return $query->first();
    }

    public static function search($searchTerm, $fields, $where = null)
    {
        $query = DB::table(static::$tableName);

        $searchTerm = array_filter(\explode(' ', $searchTerm));
        foreach ($searchTerm as $term) {
            $query->where(function ($query) use ($term, $fields) {
                foreach ($fields as $field) {
                    $query->orWhere($field, 'LIKE', "%{$term}%");
                }
            });
        }

        self::applyWhere($query, $where);

        $request = \request();
        if (static::$orderBy) {
            $query->orderBy(static::$orderBy, $request->query('order') == 'asc' ? 'asc' : 'desc');
        } else {
            $query->orderBy(static::$primaryId, 'desc');
        }

        return $query->paginate(20);
    }

    public static function getWhereOne($where = null)
    {
        $query = DB::table(static::$tableName);
        self::applyWhere($query, $where);

        return $query->first();
    }

    public static function getWhere($where = null)
    {
        $query = DB::table(static::$tableName);
        self::applyWhere($query, $where);

        return $query->orderByRaw(1, 'asc')->get();
    }

    public static function countWhere($where = null)
    {
        $query = DB::table(static::$tableName);
        self::applyWhere($query, $where);

        return $query->count();
    }

    public static function add($data)
    {
        $id = DB::table(static::$tableName)->insertGetId($data);

        return $id;
    }

    public static function addMany($data)
    {
        if (\count($data)) {
            DB::table(static::$tableName)->insert($data);
        }
    }

    public static function updateOne($id, $data, $isToken = false)
    {
        // We are not preventing updation of deleted data, otherwise we can't update restore

        $query = DB::table(static::$tableName);

        if ($isToken) {
            $query->where(static::$token, $id);
        } else {
            $query->where(static::$primaryId, $id);
        }

        $affected = $query->update($data);

        return $affected;
    }

    public static function deleteOne($id, $isToken = false)
    {
        // Let's prevent delete of non-deleted data
        // Data those are not in the trash should not be deleted
        $query = DB::table(static::$tableName);
        if (self::$isSoftDelete) {
            $metaColumns = \config('form-tool.table_meta_columns');
            $deletedAt = $metaColumns['deletedAt'] ?? 'deletedAt';

            $query->whereNotNull($deletedAt);
        }

        if ($isToken) {
            $query->where(static::$token, $id);
        } else {
            $query->where(static::$primaryId, $id);
        }

        $affected = $query->delete();

        return $affected;
    }

    public static function destroyWhere($where = null)
    {
        $query = DB::table(static::$tableName);
        self::applyWhere($query, $where);

        $affected = $query->delete();

        return $affected;
    }

    public static function setSoftDelete(bool $flag)
    {
        self::$isSoftDelete = $flag;
    }

    protected static function applyWhere($query, $where)
    {
        if ($where instanceof Closure) {
            $where($query, static::class);
        } elseif (isset($where[0]) && \is_string($where[0])) {
            $query->where($where);
        } elseif ($where) {
            foreach ($where as $expression) {
                if ($expression instanceof Closure) {
                    $expression($query, static::class);
                } elseif ($expression) {
                    $query->where($expression);
                }
            }
        }
    }
}
