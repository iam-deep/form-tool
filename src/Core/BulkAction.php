<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Models\MultipleTableModel;
use Deep\FormTool\Support\FileManager;
use Illuminate\Support\Facades\DB;
use PhpParser\Parser\Multiple;

class BulkAction
{
    private $request;
    private $callback;
    private Table $table;

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

        if ('normal' == $group) {
            if (! \config('form-tool.isDuplicate', true)) {
                unset($actions['normal']['duplicate']);
            }
            if (! Guard::hasDelete()) {
                unset($actions['normal']['delete']);
            }
        }

        if (isset($actions[$group])) {
            return $actions[$group];
        }

        return null;
    }

    public function perform(?Closure $callback = null)
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

        $this->table->crud->wantsArray();

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

        $heroField = $this->table->getBluePrint()->getHeroField();

        $callback = $this->callback;
        $filtered = [];
        $errorMessages = [];
        $heroValues = [];
        $countSuccess = 0;
        foreach ($ids as $id) {
            if (! $callback || true === $callback($id, 'duplicate')) {
                $filtered[] = $id;
                $result = $this->doDuplicate($id, $data);

                if ($result) {
                    $heroValue = '';
                    if ($heroField && ($result[$heroField] ?? null)) {
                        $heroValue = $result[$heroField];
                    }
                    $heroValues[] = $heroValue;

                    $countSuccess++;
                } else {
                    $errorMessages[] = 'Error restoring <b>'.$id.'</b>';
                }
            }
        }

        return $this->sendResponse('copied', $filtered, $countSuccess, $errorMessages, $heroValues);
    }

    protected function doDuplicate($id, $data)
    {
        $result = $this->table->getModel()->getOne($id);
        $oldData = clone $result;

        $primaryIdColumn = $this->table->getModel()->getPrimaryId();

        // Let's get the actual id if this is a token
        $id = $this->table->getModel()->isToken() ? $result->{$primaryIdColumn} : $id;

        $result->{$primaryIdColumn} = 0;
        $result = array_merge((array) $result, $data);

        // Let's clone the image
        foreach ($this->table->getBluePrint()->getList() as $input) {
            if ($input instanceof InputTypes\FileType) {
                $result[$input->getDbField()] = FileManager::copyFile($result[$input->getDbField()]);
            }
        }

        $insertId = $this->table->getModel()->add($result);

        // Creation is not successful let's return
        if (! $insertId) {
            return false;
        }

        foreach ($this->table->getBluePrint()->getList() as $input) {
            if (! $input instanceof BluePrint || ! $input->getModel()) {
                continue;
            }

            // $model = $input->getModel();

            // $foreignKey = null;
            // if ($model instanceof \stdClass) {
            //     $foreignKey = $model->foreignKey;
            // } else {
            //     if (! isset($model::$foreignKey)) {
            //         throw new \InvalidArgumentException('$foreignKey property not defined at '.$model);
            //     }

            //     $foreignKey = $model::$foreignKey;
            // }

            // $childResult = DB::table($model->table)->where([$foreignKey => $id])->orderBy($model->id, 'asc')->get();

            $model = MultipleTableModel::init($input->getModel());
            $childResult = $model->getAll($insertId);

            $insert = [];
            foreach ($childResult as $row) {
                $row = (array) $row;
                $row[$model->getPrimaryCol()] = 0;
                $row[$model->getForeignCol()] = $insertId;

                // Let's clone the image
                foreach ($input->getList() as $childInput) {
                    if ($childInput instanceof InputTypes\FileType) {
                        $row[$childInput->getDbField()] = FileManager::copyFile($row[$childInput->getDbField()]);
                    }
                }

                $insert[] = $row;
            }

            // $where = [$foreignKey => $insertId];
            // if ($model instanceof \stdClass) {
            //     DB::table($model->table)->where($where)->delete();
            //     if (\count($insert)) {
            //         DB::table($model->table)->insert($insert);
            //     }
            // } else {
            //     $model::deleteWhere($where);
            //     if (\count($insert)) {
            //         $model::addMany($insert);
            //     }
            // }

            $model->add($insertId, $insert);
        }

        ActionLogger::duplicate($this->table->getBluePrint(), $insertId, (object) $result, $oldData);

        $this->table->crud->getForm()->invokeEvent(EventType::DUPLICATE, $insertId, $result);

        return $result;
    }

    protected function delete($ids)
    {
        if (! Guard::hasDelete()) {
            return \back()->withErrors("You don't have enough permission to delete!");
        }

        $callback = $this->callback;
        $filtered = [];
        $errorMessages = [];
        $heroValues = [];
        $countSuccess = 0;
        foreach ($ids as $id) {
            if (! $callback || true === $callback($id, 'delete')) {
                $filtered[] = $id;
                $response = $this->table->crud->getForm()->delete($id);
                if (is_array($response)) {
                    if (($response['status'] ?? false) === false) {
                        $errorMessages[] = $response['message'] ?? ('Error deleting <b>'.($response['data']['heroValue'] ?? $id).'</b>');
                    } else {
                        $countSuccess++;
                        if ($response['data']['heroValue'] ?? null) {
                            $heroValues[] = $response['data']['heroValue'];
                        }
                    }
                }
            }
        }

        return $this->sendResponse('deleted', $filtered, $countSuccess, $errorMessages, $heroValues);
    }

    protected function restore($ids)
    {
        $callback = $this->callback;
        $filtered = [];
        $errorMessages = [];
        $heroValues = [];
        $countSuccess = 0;
        foreach ($ids as $id) {
            if (! $callback || true === $callback($id, 'restore')) {
                $filtered[] = $id;

                $result = null;
                if ($this->table->getModel()->isToken()) {
                    $result = $this->table->getModel()->getWhereOne([$this->table->getModel()->getTokenCol() => $id]);
                } else {
                    $result = $this->table->getModel()->getWhereOne([$this->table->getModel()->getPrimaryId() => $id]);
                }

                $pId = $id;
                if ($this->table->getModel()->isToken()) {
                    $pId = $result->{$this->table->getModel()->getPrimaryId()} ?? null;
                }

                $heroField = $this->table->getBluePrint()->getHeroField();
                $heroValue = '';
                if ($heroField && ($result->{$heroField} ?? null)) {
                    $heroValue = $result->{$heroField};
                }
                $heroValues[] = $heroValue;

                $response = $this->table->getModel()->restore($pId);
                if ($response) {
                    $countSuccess++;
                } else {
                    $errorMessages[] = 'Error restoring <b>'.($heroValue ?: $id).'</b>';
                }

                ActionLogger::restore($this->table->getBluePrint(), $pId, $result);

                $this->table->crud->getForm()->invokeEvent(EventType::RESTORE, $pId, $result);
            }
        }

        return $this->sendResponse('restored', $filtered, $countSuccess, $errorMessages, $heroValues);
    }

    protected function destroy($ids)
    {
        if (! Guard::hasDestroy()) {
            return \back()->withErrors("You don't have enough permission to delete permanently!");
        }

        $callback = $this->callback;
        $filtered = [];
        $errorMessages = [];
        $heroValues = [];
        $countSuccess = 0;
        foreach ($ids as $id) {
            if (! $callback || true === $callback($id, 'destroy')) {
                $filtered[] = $id;
                $response = $this->table->crud->getForm()->destroy($id);

                if (is_array($response)) {
                    if (($response['status'] ?? false) === false) {
                        $errorMessages[] = $response['message'] ?? ('Error destroying <b>'.($response['data']['heroValue'] ?? $id).'</b>');
                    } else {
                        $countSuccess++;
                        if ($response['data']['heroValue'] ?? null) {
                            $heroValues[] = $response['data']['heroValue'];
                        }
                    }
                }
            }
        }

        return $this->sendResponse('destroyed', $filtered, $countSuccess, $errorMessages, $heroValues);
    }

    private function sendResponse($action, $filtered, $countSuccess, $errorMessages, $heroValues)
    {
        $resource = $this->table->getBluePrint()->getForm()->getResource();
        $title = $resource->title;
        $singularTitle = $resource->singularTitle;

        if (! $filtered) {
            return \back()->withErrors('Nothing '.$action.'!');
        }
        if ($countSuccess && $heroValues) {
            return \back()->with('success', $countSuccess.' '.($countSuccess > 1 ? $title : $singularTitle).' '.$action.' successfully includes <b>'.implode(', ', $heroValues).'</b>!');
        }
        if ($countSuccess) {
            return \back()->with('success', $countSuccess.' '.($countSuccess > 1 ? $title : $singularTitle).' '.$action.' successfully!');
        }
        if ($errorMessages) {
            return \back()->withErrors($errorMessages);
        }

        return \back()->with('success', 'Selected '.$title.' '.$action.' successfully!');
    }
}
