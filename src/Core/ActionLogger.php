<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Dtos\ActionLoggerDto;
use Deep\FormTool\Enums\ActionLoggerEnum;
use Deep\FormTool\Models\MultipleTableModel;
// use Deep\FormTool\Support\ImageCache;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

// We are keeping cached images (if available) for now, other deleted files are not kept. Need to discuss should we keep deleted files

class ActionLogger
{
    public static function create(BluePrint $bluePrint, $refId, $newData = null, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::CREATE->value;

        $data = self::getCreateData($bluePrint, $newData, $refId);

        $description = null;
        $heroField = $bluePrint->getHeroField();
        if ($heroField) {
            $input = $bluePrint->getInputTypeByDbField($heroField);
            $resource = $bluePrint->getForm()->getResource();
            $title = $resource->singularTitle ?? $resource->title;

            $description = $title.' '.$input->getValue().' created';
        }

        $request = [
            'action' => $action,
            'refId' => $refId,
            'token' => $bluePrint->getForm()->getModel()->getLastToken(),
            'description' => $description,
            'data' => $data,
            'path' => $path,
        ];
        self::insert($bluePrint, $request);
    }

    /**
     * This only works for CREATE.
     */
    public static function getCreateData(BluePrint $bluePrint, ?array $newData = null, $refId = null)
    {
        $action = ActionLoggerEnum::CREATE->value;

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint) {
                $value = self::getMultipleLoggerValue(
                    $input,
                    $action,
                    [],
                    self::getMultipleRows($bluePrint, $input, $refId, $newData),
                );
                if ($value) {
                    $data['data'][$input->getLabel()] = $value;
                }

                continue;
            }

            if (! $input->isLogColumn()) {
                continue;
            }

            if ($newData) {
                $input->setValue($newData[$input->getDbField()] ?? '');
            }

            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

        return $data;
    }

    public static function duplicate(BluePrint $bluePrint, $refId, $result, $oldData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::DUPLICATE->value;

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint) {
                $value = self::getMultipleLoggerValue(
                    $input,
                    $action,
                    [],
                    self::getMultipleRows($bluePrint, $input, $refId, $result),
                );
                if ($value) {
                    $data['data'][$input->getLabel()] = $value;
                }

                continue;
            }

            if (! $input->isLogColumn()) {
                continue;
            }

            $input->setValue($result->{$input->getDbField()} ?? '');
            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

        $model = $bluePrint->getForm()->getModel();
        $token = $model->isToken() ? ($oldData->{$model->getTokenCol()} ?? '') : '';
        $data['copyFrom'] = [
            'refId' => $oldData->{$model->getPrimaryId()} ?? '',
            'token' => $token,
        ];

        $description = null;
        $heroField = $bluePrint->getHeroField();
        if ($heroField) {
            $resource = $bluePrint->getForm()->getResource();
            $title = $resource->singularTitle ?? $resource->title;
            $description = $title.' '.($result->{$heroField} ?? '').' duplicated';
        }

        $request = [
            'action' => $action,
            'refId' => $refId,
            'token' => $bluePrint->getForm()->getModel()->getLastToken(),
            'description' => $description,
            'data' => $data,
            'path' => $path,
        ];
        self::insert($bluePrint, $request);
    }

    public static function update(BluePrint $bluePrint, $refId, $oldData, $newData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::UPDATE->value;

        $newData = (object) $newData;

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint) {
                $value = self::getMultipleLoggerValue(
                    $input,
                    $action,
                    self::getMultipleRows($bluePrint, $input, $refId, $oldData),
                    self::getMultipleRows($bluePrint, $input, $refId),
                );
                if ($value) {
                    $data['data'][$input->getLabel()] = $value;
                }

                continue;
            }

            if (! $input->isLogColumn()) {
                continue;
            }

            $dbField = $input->getDbField();
            $oldValue = $oldData->{$dbField} ?? '';

            // Let's store the cache image of the old image
            // if ($oldValue && $input instanceof \Deep\FormTool\Core\InputTypes\ImageType) {
            //     $cacheImage = ImageCache::getCachedImage($oldValue);
            //     if ($cacheImage) {
            //         $oldValue = $cacheImage;
            //     }
            // }

            $value = $input->getLoggerValue($action, $oldValue);
            if ($value) {
                $data['data'][$input->getLabel()] = $value;
            }
        }

        $description = null;
        if ($bluePrint->getForm()->getCrud()->isDefaultFormat()) {
            $heroField = $bluePrint->getHeroField();
            if ($heroField) {
                $resource = $bluePrint->getForm()->getResource();
                $title = $resource->singularTitle ?? $resource->title;
                $description = $title.' '.($newData->{$heroField} ?? '').' updated';
            }
        } else {
            $title = $bluePrint->getForm()->getResource()->title;
            $description = $title.' updated';
        }

        $request = [
            'action' => $action,
            'refId' => $refId,
            'token' => self::getToken($bluePrint, $oldData),
            'description' => $description,
            'data' => $data,
            'path' => $path,
        ];
        self::insert($bluePrint, $request);
    }

    public static function delete(BluePrint $bluePrint, $refId, $oldData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::DELETE->value;

        $description = null;
        $heroField = $bluePrint->getHeroField();
        if ($heroField) {
            $resource = $bluePrint->getForm()->getResource();
            $title = $resource->singularTitle ?? $resource->title;
            $description = $title.' '.($oldData->{$heroField} ?? '').' deleted';
        }

        $request = [
            'action' => $action,
            'refId' => $refId,
            'token' => self::getToken($bluePrint, $oldData),
            'description' => $description,
            'data' => null,
            'path' => $path,
        ];
        self::insert($bluePrint, $request);
    }

    public static function destroy(BluePrint $bluePrint, $refId, $oldData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::DESTROY->value;

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint) {
                $value = self::getMultipleLoggerValue(
                    $input,
                    $action,
                    self::getMultipleRows($bluePrint, $input, $refId, $oldData),
                    [],
                );
                if ($value) {
                    $data['data'][$input->getLabel()] = $value;
                }

                continue;
            }

            if (! $input->isLogColumn()) {
                continue;
            }

            $oldValue = $oldData->{$input->getDbField()} ?? '';

            // Let's store the cache image of the old image
            // if ($oldValue && $input instanceof \Deep\FormTool\Core\InputTypes\ImageType) {
            //     $cacheImage = ImageCache::getCachedImage($oldValue);
            //     if ($cacheImage) {
            //         $oldValue = $cacheImage;
            //     }
            // }

            $input->setValue($oldValue);
            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

        $description = null;
        $heroField = $bluePrint->getHeroField();
        if ($heroField) {
            $resource = $bluePrint->getForm()->getResource();
            $title = $resource->singularTitle ?? $resource->title;
            $description = $title.' '.($oldData->{$heroField} ?? '').' permanently deleted';
        }

        $request = [
            'action' => $action,
            'refId' => $refId,
            'token' => self::getToken($bluePrint, $oldData),
            'description' => $description,
            'data' => $data,
            'path' => $path,
        ];
        self::insert($bluePrint, $request);
    }

    private static function getMultipleRows(BluePrint $bluePrint, BluePrint $input, $refId, $source = null): array
    {
        $key = $input->getKey();
        $source = is_object($source) ? (array) $source : $source;

        if (is_array($source) && array_key_exists($key, $source)) {
            return self::normalizeMultipleRows($source[$key]);
        }

        if ($refId === null) {
            return [];
        }

        if ($input->getModel()) {
            return MultipleTableModel::init($input->getModel())->getAll($refId)->all();
        }

        $parent = $bluePrint->getForm()->getModel()->getOne($refId);

        return self::normalizeMultipleRows($parent->{$key} ?? null);
    }

    private static function normalizeMultipleRows($rows): array
    {
        if (is_string($rows)) {
            $rows = json_decode($rows, true);
        } elseif ($rows instanceof \Illuminate\Support\Collection) {
            $rows = $rows->all();
        }

        if (! is_iterable($rows)) {
            return [];
        }

        return array_map(
            fn ($row) => is_object($row) ? (array) $row : $row,
            is_array($rows) ? array_values($rows) : iterator_to_array($rows, false),
        );
    }

    private static function getMultipleLoggerValue(
        BluePrint $input,
        string $action,
        array $oldRows,
        array $newRows,
    ): ?array {
        $columns = [];
        foreach ($input->getInputList() as $childInput) {
            if (! $childInput instanceof BluePrint && $childInput->isLogColumn()) {
                $columns[] = $childInput->getLabel();
            }
        }

        if (! $columns) {
            return null;
        }

        $oldRows = self::formatMultipleRows($input, $oldRows, $action === ActionLoggerEnum::DESTROY->value ? $action : ActionLoggerEnum::CREATE->value);
        $newRows = self::formatMultipleRows($input, $newRows, ActionLoggerEnum::CREATE->value);

        if ($action === ActionLoggerEnum::UPDATE->value && $oldRows == $newRows) {
            return null;
        }

        return [
            'type' => 'table',
            'columns' => $columns,
            'data' => [$oldRows, $newRows],
        ];
    }

    private static function formatMultipleRows(BluePrint $input, array $rows, string $action): array
    {
        $formatted = [];
        foreach ($rows as $row) {
            $row = (array) $row;
            $formattedRow = [];
            foreach ($input->getInputList() as $childInput) {
                if ($childInput instanceof BluePrint || ! $childInput->isLogColumn()) {
                    continue;
                }

                $childInput->setValue($row[$childInput->getDbField()] ?? null);
                $formattedRow[$childInput->getLabel()] = $childInput->getLoggerValue($action);
            }
            $formatted[] = $formattedRow;
        }

        return $formatted;
    }

    public static function getMultipleTableDiff(array $columns, array $oldRows, array $newRows): array
    {
        $identifier = $columns[0] ?? null;
        $oldKeys = self::getUniqueMultipleTableRowKeys($oldRows, $identifier);
        $newKeys = self::getUniqueMultipleTableRowKeys($newRows, $identifier);
        $matchByIdentifier = $identifier !== null && $oldKeys !== null && $newKeys !== null;

        $matchedOldRows = [];
        $diffRows = [];
        foreach ($newRows as $newIndex => $newRow) {
            $oldIndex = $matchByIdentifier
                ? ($oldKeys[self::getMultipleTableRowKey($newRow, $identifier)] ?? null)
                : (array_key_exists($newIndex, $oldRows) ? $newIndex : null);

            if ($oldIndex === null) {
                $diffRows[] = self::makeMultipleTableDiffRow('add', [], $newRow, $columns, $identifier);

                continue;
            }

            $matchedOldRows[$oldIndex] = true;
            $diffRow = self::makeMultipleTableDiffRow('update', $oldRows[$oldIndex], $newRow, $columns, $identifier);
            if ($diffRow) {
                $diffRows[] = $diffRow;
            }
        }

        foreach ($oldRows as $oldIndex => $oldRow) {
            if (! isset($matchedOldRows[$oldIndex])) {
                $diffRows[] = self::makeMultipleTableDiffRow('remove', $oldRow, [], $columns, $identifier);
            }
        }

        $visibleColumns = [];
        foreach ($columns as $column) {
            foreach ($diffRows as $row) {
                if (array_key_exists($column, $row['cells'])) {
                    $visibleColumns[] = $column;

                    break;
                }
            }
        }

        return [
            'columns' => $visibleColumns,
            'rows' => $diffRows,
        ];
    }

    private static function getUniqueMultipleTableRowKeys(array $rows, ?string $identifier): ?array
    {
        if ($identifier === null) {
            return null;
        }

        $keys = [];
        foreach ($rows as $index => $row) {
            if (! array_key_exists($identifier, $row)) {
                return null;
            }

            $identifierValue = self::getMultipleTableIdentifierValue($row[$identifier]);
            if ($identifierValue === null) {
                return null;
            }

            $key = serialize($identifierValue);
            if (array_key_exists($key, $keys)) {
                return null;
            }
            $keys[$key] = $index;
        }

        return $keys;
    }

    private static function getMultipleTableRowKey(array $row, string $identifier): string
    {
        return serialize(self::getMultipleTableIdentifierValue($row[$identifier] ?? null));
    }

    private static function getMultipleTableIdentifierValue($value)
    {
        if (is_array($value) && array_key_exists('data', $value)) {
            $value = $value['data'];
        }

        if ($value === null || $value === [] || (is_string($value) && trim($value) === '')) {
            return null;
        }

        return $value;
    }

    private static function makeMultipleTableDiffRow(
        string $type,
        array $oldRow,
        array $newRow,
        array $columns,
        ?string $identifier,
    ): ?array {
        $cells = [];
        $hasChanges = $type !== 'update';
        foreach ($columns as $column) {
            $hasOld = array_key_exists($column, $oldRow);
            $hasNew = array_key_exists($column, $newRow);
            $oldValue = $hasOld ? $oldRow[$column] : null;
            $newValue = $hasNew ? $newRow[$column] : null;

            if ($oldValue != $newValue) {
                $hasChanges = true;
            }

            if ($type === 'update' && $column !== $identifier && $oldValue == $newValue) {
                continue;
            }

            $cells[$column] = [
                'old' => $oldValue,
                'new' => $newValue,
                'identifier' => $column === $identifier,
            ];
        }

        if (! $hasChanges) {
            return null;
        }

        return [
            'type' => $type,
            'cells' => $cells,
        ];
    }

    public static function restore(BluePrint $bluePrint, $refId, $oldData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::RESTORE->value;

        $description = null;
        $heroField = $bluePrint->getHeroField();
        if ($heroField) {
            $resource = $bluePrint->getForm()->getResource();
            $title = $resource->singularTitle ?? $resource->title;
            $description = $title.' '.($oldData->{$heroField} ?? '').' restored';
        }

        $request = [
            'action' => $action,
            'refId' => $refId,
            'token' => self::getToken($bluePrint, $oldData),
            'description' => $description,
            'data' => null,
            'path' => $path,
        ];
        self::insert($bluePrint, $request);
    }

    private static function insert(BluePrint $bluePrint, $data)
    {
        $request = request();

        $insert = [
            'module' => $bluePrint->getForm()->getResource()->title,
            'route' => $bluePrint->getForm()->getResource()->route,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'createdByName' => Auth::user()?->name,
        ];

        if ($bluePrint->getForm()->isOnlyForAdmin()) {
            $insert['isClassunify'] = true;
        }

        $data['data'] = $data['data'] ? \json_encode($data['data']) : null;
        $insert = array_merge($insert, $data);

        (new DataModel())->db('action_logs', 'id')->add($insert);
    }

    public static function log(ActionLoggerDto|array $actions)
    {
        $request = request();

        $actions = Arr::wrap($actions);

        $createAt = now();
        $createBy = Auth::id();
        $createdByName = Auth::user()?->name;

        $insert = [];
        $isOnlyForAdmin = collect($actions)->contains(
            fn (ActionLoggerDto $action) => $action->isOnlyForAdmin()
        );

        /** @var ActionLoggerDto $action */
        foreach ($actions as $action) {
            $row = [
                'action' => $action->action,
                'refId' => $action->id,
                'token' => $action->token,
                'description' => $action->description,
                'data' => $action->getJsonData(),
                'extraData' => $action->extraData,
                'module' => $action->moduleTitle,
                'route' => $action->route,
                'path' => $action->fullPath,
                'ipAddress' => $request->ip(),
                'userAgent' => $request->userAgent(),
                'createdAt' => $createAt,
                'createdBy' => $createBy,
                'createdByName' => $createdByName,
            ];

            if ($isOnlyForAdmin) {
                $row['isClassunify'] = $action->isOnlyForAdmin();
            }

            $insert[] = $row;
        }

        (new DataModel())->db('action_logs', 'id')->addMany($insert);
    }

    private static function getToken(BluePrint $bluePrint, $result)
    {
        $model = $bluePrint->getForm()->getModel();

        return $model->isToken() ? ($result->{$model->getTokenCol()} ?? null) : null;
    }
}
