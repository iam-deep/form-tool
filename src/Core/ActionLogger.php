<?php

namespace Deep\FormTool\Core;

// TODO: Multiple Logger
// TODO: Keep deleted files and images

class ActionLogger
{
    public static function create(BluePrint $bluePrint, $refId, $newData = null, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = 'create';

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint || ! $input->isLogColumn()) {
                continue;
            }

            if ($newData) {
                $input->setValue($newData[$input->getDbField()] ?? '');
            }

            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

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

    public static function duplicate(BluePrint $bluePrint, $refId, $result, $oldData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = 'duplicate';

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint || ! $input->isLogColumn()) {
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

        $action = 'update';

        $newData = (object) $newData;

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint || ! $input->isLogColumn()) {
                continue;
            }

            $dbField = $input->getDbField();
            $oldValue = $oldData->{$dbField} ?? '';

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

        $action = 'delete';

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

        $action = 'destroy';

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint || ! $input->isLogColumn()) {
                continue;
            }

            $input->setValue($oldData->{$input->getDbField()} ?? '');
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

    public static function restore(BluePrint $bluePrint, $refId, $oldData, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = 'restore';

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
        $insert = [
            'module' => $bluePrint->getForm()->getResource()->title,
            'route' => $bluePrint->getForm()->getResource()->route,
            'createdByName' => Auth::user()->name,
        ];

        $data['data'] = $data['data'] ? \json_encode($data['data']) : null;
        $insert = array_merge($insert, $data);

        (new DataModel())->db('action_logs', 'id')->add($insert);
    }

    public static function log($action, $id, $data, $route = null, $moduleTitle = null, $options = null)
    {
        $dataTitle = $options['dataTitle'] ?? null;
        $description = $options['description'] ?? null;
        $token = $options['token'] ?? null;

        if (! $description && $moduleTitle && $dataTitle) {
            $suffix = '';
            if ($action == 'create') {
                $suffix = 'created';
            } elseif ($action == 'update') {
                $suffix = 'updated';
            } elseif ($action == 'delete') {
                $suffix = 'deleted';
            } elseif ($action == 'destroy') {
                $suffix = 'permanently deleted';
            } elseif ($action == 'restore') {
                $suffix = 'restored';
            } elseif ($action == 'duplicate') {
                $suffix = 'duplicated';
            }

            $description = $moduleTitle.' '.$dataTitle.' '.$suffix;
        }

        $insert = [
            'action' => $action,
            'refId' => $id,
            'token' => $token,
            'description' => $description,
            'data' => $data ? \json_encode($data) : null,
            'module' => $moduleTitle,
            'route' => $route,
            'createdByName' => Auth::user()->name,
        ];

        return (new DataModel())->db('action_logs', 'id')->add($insert);
    }

    private static function getToken(BluePrint $bluePrint, $result)
    {
        $model = $bluePrint->getForm()->getModel();

        return $model->isToken() ? ($result->{$model->getTokenCol()} ?? null) : null;
    }
}
