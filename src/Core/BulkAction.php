<?php

namespace Biswadeep\FormTool\Core;

use Closure;
use Illuminate\Support\Facades\DB;

class BulkAction
{
    private $request;
    private $callback;
    private $table;

    public function setTable($table)
    {
        $this->table = $table;
    }

    public function getActions(string $group)
    {
        $actions = [
            'normal' => [
                'duplicate' => 'Duplicate',
                'delete' => 'Delete',
            ],
            'trash' => [
                'restore' => 'Restore',
                'destroy' => 'Delete Permanently',
            ],
        ];

        if (isset($actions[$group])) {
            return $actions[$group];
        }

        return null;
    }

    public function perform(Closure $callback = null)
    {
        $this->request = \request();
        $this->callback = $callback;

        $bulkAction = \trim($this->request->post('bulkAction'));
        if (! $bulkAction) {
            return \back()->withErrors('Please select bulk action!');
        }

        $ids = array_filter(\explode(',', $this->request->post('ids')));
        if (! $ids) {
            return \back()->withErrors('Please select some rows to delete!');
        }
        
        $callback = $this->callback;
        $filtered = [];
        foreach ($ids as $id) {
            if (! $callback || false !== $callback($id, $bulkAction)) {
                $filtered[] = $id;
            }
        }

        $response = null;
        switch ($bulkAction)
        {
            case 'duplicate':
                $response = $this->duplicate($filtered);
                break;

            case 'delete':
                $response = $this->delete($filtered);
                break;

            case 'restore':
                $response = $this->restore($filtered);
                break;

            case 'destroy':
                $response = $this->destroy($filtered);
                break;

            default:
                $response = \back();
        }

        return $response;
    }

    protected function duplicate($ids)
    {
        if (! Guard::hasCreate()) {
            return \back()->withErrors("You don't have enough permission to create!");
        }

        if (! $ids) {
            return \back()->withErrors('Nothing copied!');
        }

        $metaColumns = \config('form-tool.table_meta_columns', $this->table->getTableMetaColumns());

        $data = [];
        $data[$metaColumns['updatedBy'] ?? 'updatedBy'] = null;
        $data[$metaColumns['updatedAt'] ?? 'updatedAt'] = null;
        $data[$metaColumns['createdBy'] ?? 'createdBy'] = Auth::user()->userId;
        $data[$metaColumns['createdAt'] ?? 'createdAt'] = \date('Y-m-d H:i:s');

        foreach ($ids as $id) {
            $result = $this->table->getModel()->getOne($id);

            $primaryIdColumn = $this->table->getModel()->getPrimaryId();

            // Let's get the actual id if this is a token
            $id = $this->table->getModel()->isToken() ? $result->{$primaryIdColumn} : $id;

            $result->{$primaryIdColumn} = 0;
            $result = array_merge((array) $result, $data);

            $insertId = $this->table->getModel()->add($result);

            // Creation is not successful let's move on
            if (! $insertId) {
                continue;
            }

            foreach ($this->table->getBluePrint()->getList() as $input) {
                if (! $input instanceof BluePrint || ! $input->getModel()) {
                    continue;
                }
    
                $model = $input->getModel();
    
                $foreignKey = null;
                if ($model instanceof \stdClass) {
                    $foreignKey = $model->foreignKey;
                } else {
                    if (! isset($model::$foreignKey)) {
                        throw new \Exception('$foreignKey property not defined at '.$model);
                    }
    
                    $foreignKey = $model::$foreignKey;
                }
             
                $childResult = DB::table($model->table)->where([$foreignKey => $id])->orderBy($model->id, 'asc')->get();
                
                $insert = [];
                foreach ($childResult as $row) {
                    $row = (array) $row;
                    $row[$model->id] = 0;
                    $row[$foreignKey] = $insertId;

                    $insert[] = $row;
                }

                // TODO: Need to do this in the data model, it's not BulkAction or Form's job
                $where = [$foreignKey => $insertId];
                if ($model instanceof \stdClass) {
                    DB::table($model->table)->where($where)->delete();
                    if (\count($insert)) {
                        DB::table($model->table)->insert($insert);
                    }
                } else {
                    $model::deleteWhere($where);
                    if (\count($insert)) {
                        $model::addMany($insert);
                    }
                }
            }
        }

        return \back()->with('success', 'Selected rows copied successfully!');
    }

    protected function delete($ids)
    {
        if (! Guard::hasDelete()) {
            return \back()->withErrors("You don't have enough permission to delete!");
        }

        if (! $ids) {
            return \back()->withErrors('Nothing deleted!');
        }

        foreach ($ids as $id) {
            $this->table->crud->getForm()->delete($id);
        }

        return \back()->with('success', 'Selected rows deleted successfully!');
    }

    protected function restore($ids)
    {
        if (! $ids) {
            return \back()->withErrors('Nothing restored!');
        }
        
        $metaColumns = \config('form-tool.table_meta_columns', $this->table->getTableMetaColumns());

        $data = [];
        $data[$metaColumns['deletedBy'] ?? 'deletedBy'] = null;
        $data[$metaColumns['deletedAt'] ?? 'deletedAt'] = null;

        foreach ($ids as $id) {    
            $affected = $this->table->getModel()->updateOne($id, $data);
        }

        return \back()->with('success', 'Selected rows restored successfully!');
    }

    protected function destroy($ids)
    {
        if (! Guard::hasDestroy()) {
            return \back()->withErrors("You don't have enough permission to delete permanently!");
        }

        if (! $ids) {
            return \back()->withErrors('Nothing deleted!');
        }

        foreach ($ids as $id) {
            $this->table->crud->getForm()->destroy($id);
        }

        return \back()->with('success', 'Selected rows deleted permanently!');
    }
}
