<?php

namespace Biswadeep\FormTool\Models;

use Closure;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BaseModel extends Model
{
    // All the public variables/methods can be overridden from the child class
    public static $tableName = '';
    public static $primaryId = 'id';
    public static $token = 'token';
    public static $foreignKey = '';
    public static $alias = '';

    // Don't be confuse with primary id, this would be the title field for an blog post table
    public static $primaryCol = null;

    public static $orderByCol = null;
    public static $orderByDirection = 'asc';

    public static $limit = 20;

    // If you dont' want to soft delete for this module then call softDelete(false) on CRUD
    protected static $isSoftDelete = true;

    public static function getAll($where = null)
    {
        $query = DB::table(static::$tableName.' as '.self::$alias);

        self::applyWhere($query, $where);

        if (static::$orderByCol) {
            $query->orderBy(static::$orderByCol, static::$orderByDirection);
        } else {
            $query->orderBy(self::$alias.'.'.static::$primaryId, 'desc');
        }

        return $query->paginate(static::$limit);
    }

    public static function getOne($id, $isToken = false)
    {
        $query = DB::table(static::$tableName.' as '.self::$alias);
        if (self::$isSoftDelete) {
            $metaColumns = \config('form-tool.table_meta_columns');
            $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

            $query->whereNull(self::$alias.'.'.$deletedAt);
        }

        if ($isToken) {
            $query->where(self::$alias.'.'.static::$token, $id);
        } else {
            $query->where(self::$alias.'.'.static::$primaryId, $id);
        }

        return $query->first();
    }

    public static function search($searchTerm, $fields, $where = null)
    {
        $query = DB::table(static::$tableName.' as '.self::$alias);

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
        if (static::$orderByCol) {
            $query->orderBy(static::$orderByCol, static::$orderByDirection);
        } else {
            $query->orderBy(self::$alias.'.'.static::$primaryId, 'desc');
        }

        return $query->paginate(static::$limit);
    }

    public static function getWhereOne($where = null)
    {
        $query = DB::table(static::$tableName.' as '.self::$alias);
        self::applyWhere($query, $where);

        return $query->first();
    }

    public static function getWhere($where = null, $orderBy = null, $direction = 'asc')
    {
        $query = DB::table(static::$tableName.' as '.self::$alias);
        self::applyWhere($query, $where);

        if ($orderBy) {
            $query->orderBy($orderBy, $direction ?? 'asc');
        } elseif (static::$orderByCol) {
            $query->orderBy(static::$orderByCol, static::$orderByDirection);
        } else {
            $query->orderByRaw(2, 'asc');
        }

        return $query->get();
    }

    public static function countWhere($where = null)
    {
        $query = DB::table(static::$tableName.' as '.self::$alias);
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
            $deletedAt = ($metaColumns['deletedAt'] ?? 'deletedAt') ?: 'deletedAt';

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
        $query = DB::table(static::$tableName.' as '.self::$alias);
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
        } elseif ($where && \is_string($where[array_key_first($where)])) {
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
