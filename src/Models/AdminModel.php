<?php

namespace Biswadeep\FormTool\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class AdminModel extends Model
{
    use HasFactory;

    public static $tableName = '';
    public static $primaryId = 'id';
    public static $orderBy = 'id';
    public static $foreignKey = '';

    public static function setup($tableName, $primaryId, $orderBy = null, $foreignKey = null)
    {
        static::$tableName = $tableName ?: static::$tableName;
        static::$primaryId = $primaryId ?: static::$primaryId;
        static::$orderBy = $orderBy ?: static::$orderBy;
        static::$foreignKey = $foreignKey ?: static::$foreignKey;
    }

    public static function getAll()
    {
        return DB::table(static::$tableName)->orderBy(static::$primaryId, 'desc')->paginate(20);
    }

    public static function getOne($id)
    {
        return DB::table(static::$tableName)->where(static::$primaryId, $id)->first();
    }

    public static function search($searchTerm, $fields)
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

        return $query->orderBy(static::$primaryId, 'desc')->paginate(20);
    }

    public static function getWhere($where)
    {
        return DB::table(static::$tableName)->orderBy(static::$orderBy, 'asc')->where($where)->get();
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
        $affected = DB::table(static::$tableName)->where(static::$primaryId, $id)->update($data);

        return $affected;
    }

    public static function deleteOne($id)
    {
        $affected = DB::table(static::$tableName)->where(static::$primaryId, $id)->delete();

        return $affected;
    }

    public static function deleteWhere($where)
    {
        $affected = DB::table(static::$tableName)->where($where)->delete();

        return $affected;
    }
}
