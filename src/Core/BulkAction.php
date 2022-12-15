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

        $response = null;
        switch ($bulkAction) {
            case 'duplicate':
                $response = $this->duplicate($ids);
                break;

            case 'delete':
                $response = $this->delete($ids);
                break;

            case 'restore':
                $response = $this->restore($ids);
                break;

            case 'destroy':
                $response = $this->destroy($ids);
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

        $metaColumns = \config('form-tool.table_meta_columns', $this->table->getTableMetaColumns());

        $data = [];
        $data[$metaColumns['updatedBy'] ?? 'updatedBy'] = null;
        $data[$metaColumns['updatedAt'] ?? 'updatedAt'] = null;
        $data[$metaColumns['createdBy'] ?? 'createdBy'] = Auth::id();
        $data[$metaColumns['createdAt'] ?? 'createdAt'] = \date('Y-m-d H:i:s');

        $callback = $this->callback;
        $filtered = [];
        foreach ($ids as $id) {
            if (! $callback || false !== $callback($id, 'duplicate')) {
                $filtered[] = $id;
                $this->doDuplicate($id, $data);
            }
        }

        if (! $filtered) {
            return \back()->withErrors('Nothing copied!');
        }

        return \back()->with('success', 'Selected rows copied successfully!');
    }

    protected function doDuplicate($id, $data)
    {
        // TODO: Duplicate the actual images and files

        $result = $this->table->getModel()->getOne($id);
        $oldData = clone $result;

        $primaryIdColumn = $this->table->getModel()->getPrimaryId();

        // Let's get the actual id if this is a token
        $id = $this->table->getModel()->isToken() ? $result->{$primaryIdColumn} : $id;

        $result->{$primaryIdColumn} = 0;
        $result = array_merge((array) $result, $data);

        $insertId = $this->table->getModel()->add($result);

        // Creation is not successful let's return
        if (! $insertId) {
            return;
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

        ActionLogger::duplicate($this->table->getBluePrint(), $insertId, (object) $result, $oldData);
    }

    protected function delete($ids)
    {
        if (! Guard::hasDelete()) {
            return \back()->withErrors("You don't have enough permission to delete!");
        }

        $callback = $this->callback;
        $filtered = [];
        foreach ($ids as $id) {
            if (! $callback || false !== $callback($id, 'delete')) {
                $filtered[] = $id;
                $this->table->crud->getForm()->delete($id);
            }
        }

        if (! $filtered) {
            return \back()->withErrors('Nothing deleted!');
        }

        return \back()->with('success', 'Selected rows deleted successfully!');
    }

    protected function restore($ids)
    {
        $callback = $this->callback;
        $filtered = [];
        foreach ($ids as $id) {
            if (! $callback || false !== $callback($id, 'restore')) {
                $filtered[] = $id;
                $affected = $this->table->getModel()->restore($id);

                $result = $this->table->getModel()->getOne($id);

                $pId = $id;
                if ($this->table->getModel()->isToken()) {
                    $pId = $result->{$this->table->getModel()->getPrimaryId()} ?? null;
                }
                ActionLogger::restore($this->table->getBluePrint(), $pId, $result);
            }
        }

        if (! $filtered) {
            return \back()->withErrors('Nothing restored!');
        }

        return \back()->with('success', 'Selected rows restored successfully!');
    }

    protected function destroy($ids)
    {
        if (! Guard::hasDestroy()) {
            return \back()->withErrors("You don't have enough permission to delete permanently!");
        }

        $callback = $this->callback;
        $filtered = [];
        foreach ($ids as $id) {
            if (! $callback || false !== $callback($id, 'destroy')) {
                $filtered[] = $id;
                $this->table->crud->getForm()->destroy($id);
            }
        }

        if (! $filtered) {
            return \back()->withErrors('Nothing deleted!');
        }

        return \back()->with('success', 'Selected rows deleted permanently!');
    }
}
