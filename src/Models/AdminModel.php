<?php

namespace Biswadeep\FormTool\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Closure;

class AdminModel extends Model
{
    use HasFactory;

    public static $tableName = '';
    public static $primaryId = 'id';
    public static $orderBy = 'id';
    public static $foreignKey = '';

    public static $isSoftDelete = true;
    public static $columnDeletedAt = 'deletedAt';

    public static function setup($tableName, $primaryId, $orderBy = null, $foreignKey = null)
    {
        static::$tableName = $tableName ?: static::$tableName;
        static::$primaryId = $primaryId ?: static::$primaryId;
        static::$orderBy = $orderBy ?: static::$orderBy;
        static::$foreignKey = $foreignKey ?: static::$foreignKey;
    }

    public static function getAll($isFromTrash = false)
    {
        $query = DB::table(static::$tableName);
        if (static::$isSoftDelete) {
            if ($isFromTrash) {
                $query->whereNotNull(static::$columnDeletedAt);
            } else {
                $query->whereNull(static::$columnDeletedAt);
            }
        }

        return $query->orderBy(static::$primaryId, 'desc')->paginate(20);
    }

    public static function getOne($id)
    {
        $query = DB::table(static::$tableName);
        if (static::$isSoftDelete) {
            $query->whereNull(static::$columnDeletedAt);
        }

        return $query->where(static::$primaryId, $id)->first();
    }

    public static function search($searchTerm, $fields, $isFromTrash = false)
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

        if (static::$isSoftDelete) {
            if ($isFromTrash) {
                $query->whereNotNull(static::$columnDeletedAt);
            } else {
                $query->whereNull(static::$columnDeletedAt);
            }
        }

        return $query->orderBy(static::$primaryId, 'desc')->paginate(20);
    }

    public static function getWhere($where)
    {
        $query = DB::table(static::$tableName);
        if (static::$isSoftDelete) {
            $query->whereNull(static::$columnDeletedAt);
        }

        return $query->orderBy(static::$orderBy, 'asc')->where($where)->get();
    }

    public static function countWhere(Closure $where = null)
    {
        $query = DB::table(static::$tableName);
        if ($where) {
            $where($query, AdminModel::class);
        }

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

    public static function updateOne($id, $data)
    {
        // Let's prevent update of deleted data
        $query = DB::table(static::$tableName);
        if (static::$isSoftDelete) {
            $query->whereNull(static::$columnDeletedAt);
        }

        $affected = $query->where(static::$primaryId, $id)->update($data);

        return $affected;
    }

    public static function deleteOne($id)
    {
        // Let's prevent delete of non-deleted data
        // Data those are not in the trash should not be deleted
        $query = DB::table(static::$tableName);
        if (static::$isSoftDelete) {
            $query->whereNotNull(static::$columnDeletedAt);
        }

        $affected = $query->where(static::$primaryId, $id)->delete();

        return $affected;
    }

    public static function destroyWhere($where)
    {
        $affected = DB::table(static::$tableName)->where($where)->delete();

        return $affected;
    }
}
