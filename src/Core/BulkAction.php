<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Core\InputTypes\BaseDateTimeType;
use Deep\FormTool\Core\InputTypes\CheckboxType;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\SelectType;
use Deep\FormTool\Core\InputTypes\TextType;
use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Models\MultipleTableModel;
use Deep\FormTool\Support\FileManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class BulkAction
{
    private $request;
    private $callback;
    private Table $table;
    private ?string $duplicateError = null;

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
            if (! $this->table->crud->isDuplicateEnabled()) {
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
        if (! $this->table->crud->isDuplicateEnabled()) {
            return \back()->withErrors('Duplicating is disabled for this module.');
        }

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
                $this->duplicateError = null;

                try {
                    $result = $this->doDuplicate($id, $data);
                } catch (Throwable $exception) {
                    $result = false;
                    $this->duplicateError = $exception->getMessage();
                }

                if ($result) {
                    $heroValue = '';
                    if ($heroField && ($result[$heroField] ?? null)) {
                        $heroValue = $result[$heroField];
                    }
                    $heroValues[] = $heroValue;

                    $countSuccess++;
                } else {
                    $errorMessages[] = $this->duplicateError ?: 'Error duplicating <b>'.$id.'</b>';
                }
            }
        }

        return $this->sendResponse('copied', $filtered, $countSuccess, $errorMessages, $heroValues);
    }

    protected function doDuplicate($id, $data)
    {
        $result = $this->table->getModel()->getOne($id);
        if (! $result) {
            $this->duplicateError = 'Data not found for duplication.';

            return false;
        }

        $oldData = clone $result;

        $primaryIdColumn = $this->table->getModel()->getPrimaryId();

        // Let's get the actual id if this is a token
        $id = $this->table->getModel()->isToken() ? $result->{$primaryIdColumn} : $id;

        $duplicateData = $this->formatDuplicateData(
            $this->table->getBluePrint(),
            $this->createDuplicateData($id, $result),
        );
        $onDuplicate = $this->table->crud->getOnDuplicate();

        if ($onDuplicate) {
            $duplicateData = $onDuplicate($duplicateData, $oldData);
            if (! is_array($duplicateData)) {
                throw new FormToolException('onDuplicate() must return an array.');
            }
        } else {
            $duplicateData = $this->regenerateUniqueValues($duplicateData);
        }

        $validation = $this->table->crud->getForm()->validateDuplicateData($duplicateData);
        if ($validation !== true) {
            $this->duplicateError = $validation['message'] ?? 'Duplicate validation failed.';

            return false;
        }

        $copiedFiles = [];
        try {
            $duplicateData = $this->copyDuplicateFiles(
                $this->table->getBluePrint(),
                $duplicateData,
                $copiedFiles,
            );

            $stored = DB::transaction(function () use ($duplicateData, $oldData) {
                $result = $this->table->crud->getForm()->storeDuplicateData($duplicateData, $oldData);
                if (! $result) {
                    throw new FormToolException('Failed to insert duplicated data.');
                }

                return $result;
            });
        } catch (Throwable $exception) {
            $this->deleteCopiedFiles($copiedFiles);

            throw $exception;
        }

        return $stored['data'];
    }

    private function copyDuplicateFiles(BluePrint $bluePrint, array $data, array &$copiedFiles): array
    {
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof InputTypes\FileType) {
                $field = $input->getDbField();
                $data[$field] = $this->copyDuplicateFile($input, $data[$field] ?? null, $copiedFiles);

                continue;
            }

            if (! $input instanceof BluePrint) {
                continue;
            }

            $key = $input->getKey();
            foreach (($data[$key] ?? []) as $index => $row) {
                $data[$key][$index] = $this->copyDuplicateFiles($input, (array) $row, $copiedFiles);
            }
        }

        return $data;
    }

    private function copyDuplicateFile(InputTypes\FileType $input, $value, array &$copiedFiles): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $copy = $this->copyFileValue($input, $value);
        if (! $copy) {
            throw new FormToolException('Failed to copy file "'.$value.'" while duplicating data.');
        }

        $copiedFiles[] = ['path' => $copy, 'disk' => $input->getDisk()];

        return $copy;
    }

    private function deleteCopiedFiles(array $copiedFiles): void
    {
        foreach (array_reverse($copiedFiles) as $file) {
            FileManager::deleteFile($file['path'], $file['disk']);
        }
    }

    private function createDuplicateData($id, object $result): array
    {
        $duplicateData = (array) $result;

        foreach ($this->table->getBluePrint()->getInputList() as $input) {
            if (! $input instanceof BluePrint) {
                continue;
            }

            if ($input->getModel()) {
                $duplicateData[$input->getKey()] = MultipleTableModel::init($input->getModel())
                    ->getAll($id)
                    ->map(fn ($row) => (array) $row)
                    ->all();
            } elseif (isset($duplicateData[$input->getKey()])) {
                $duplicateData[$input->getKey()] = json_decode($duplicateData[$input->getKey()], true) ?: [];
            }
        }

        return $duplicateData;
    }

    private function formatDuplicateData(BluePrint $bluePrint, array $data): array
    {
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof SelectType || $input instanceof CheckboxType) {
                $field = $input->getDbField();
                $data[$field] = $input->getDuplicateValue($data[$field] ?? null);

                continue;
            }

            if ($input instanceof BaseDateTimeType) {
                $field = $input->getDbField();
                $data[$field] = $input->getNiceValue($data[$field] ?? null);

                continue;
            }

            if (! $input instanceof BluePrint) {
                continue;
            }

            $key = $input->getKey();
            foreach (($data[$key] ?? []) as $index => $row) {
                $data[$key][$index] = $this->formatDuplicateData($input, (array) $row);
            }
        }

        return $data;
    }

    private function regenerateUniqueValues(array $data): array
    {
        foreach ($this->table->getBluePrint()->getInputList() as $input) {
            if (! $input instanceof TextType || ! $input->isUnique()) {
                continue;
            }

            $field = $input->getDbField();
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                continue;
            }

            $data[$field] = $this->nextUniqueValue($input, $data[$field] ?? '');
        }

        $columns = $this->table->crud->getForm()->getUniqueColumns();
        if ($columns && $this->combinationExists($columns, $data)) {
            $eligible = null;
            foreach ($columns as $column) {
                $column = $this->columnName($column);
                $input = $this->table->getBluePrint()->getInputTypeByDbField($column);
                if ($input instanceof TextType && $input->getType() === InputType::TEXT) {
                    $eligible = $input;
                }
            }

            if (! $eligible) {
                throw new FormToolException(
                    'Composite unique fields require onDuplicate() because no text field can use Copy N.'
                );
            }

            $field = $eligible->getDbField();
            $original = (string) ($data[$field] ?? '');
            $copy = 1;
            do {
                $data[$field] = $this->copyValue($eligible, $original, $copy++);
            } while ($this->combinationExists($columns, $data));
        }

        return $data;
    }

    private function nextUniqueValue(TextType $input, $value): string
    {
        $copy = 1;
        do {
            $candidate = $this->copyValue($input, (string) $value, $copy++);
            $where = [[$input->getDbField() => $candidate]];
            if ($input->uniqueClosure) {
                $where[] = $input->uniqueClosure;
            }
        } while ($this->table->getModel()->countWhere($where));

        return $candidate;
    }

    private function copyValue(TextType $input, string $value, int $copy): string
    {
        $value = trim($value).' Copy '.$copy;

        return $input->isSlug() ? Str::slug($value) : $value;
    }

    private function combinationExists(array $columns, array $data): bool
    {
        $where = [];
        foreach ($columns as $column) {
            $where[] = [$column => $data[$this->columnName($column)] ?? null];
        }

        return (bool) $this->table->getModel()->countWhere($where);
    }

    private function columnName(string $column): string
    {
        return false !== strpos($column, '.') ? trim(explode('.', $column)[1] ?? '') : trim($column);
    }

    protected function copyFileValue(InputTypes\FileType $input, $value): ?string
    {
        return FileManager::copyFile(
            $value,
            $input->getDisk(),
            $input->getFileVisibility(),
        );
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
                if (\is_array($response)) {
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

                $column = $this->table->getModel()->getAlias().$this->table->getModel()->getPrimaryId();
                if ($this->table->getModel()->isToken()) {
                    $column = $this->table->getModel()->getAlias().$this->table->getModel()->getTokenCol();
                }

                $result = $this->table->getModel()->getWhereOne([$column => $id]);

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

                $validation = $this->table->crud->getForm()->validateRestoreData($pId, $result);
                if ($validation !== true) {
                    $errorMessages[] = $validation['message'] ?? ('Error restoring <b>'.($heroValue ?: $id).'</b>');
                    continue;
                }

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

                if (\is_array($response)) {
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
