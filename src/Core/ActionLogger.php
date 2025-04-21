<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Dtos\ActionLoggerDto;
use Deep\FormTool\Enums\ActionLoggerEnum;
use Deep\FormTool\Support\ImageCache;

// TODO: Multiple Logger
// We are keeping cached images (if available) for now, other deleted files are not kept. Need to discuss should we keep deleted files

class ActionLogger
{
    public static function create(BluePrint $bluePrint, $refId, $newData = null, $path = null)
    {
        if (! $bluePrint->getForm()->isLogAction()) {
            return;
        }

        $action = ActionLoggerEnum::CREATE->value;

        $data = self::getCreateData($bluePrint, $newData);

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
    public static function getCreateData(BluePrint $bluePrint, ?array $newData = null)
    {
        $action = ActionLoggerEnum::CREATE->value;

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

        $action = ActionLoggerEnum::UPDATE->value;

        $newData = (object) $newData;

        $data = [];
        foreach ($bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint || ! $input->isLogColumn()) {
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
            if ($input instanceof BluePrint || ! $input->isLogColumn()) {
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
            'createdByName' => Auth::user()->name,
        ];

        $data['data'] = $data['data'] ? \json_encode($data['data']) : null;
        $insert = array_merge($insert, $data);

        (new DataModel())->db('action_logs', 'id')->add($insert);
    }

    public static function log(ActionLoggerDto $action)
    {
        $description = null;
        if (! $action->description && $action->moduleTitle && $action->nameOfTheData) {
            $suffix = '';
            if ($action->action == ActionLoggerEnum::CREATE) {
                $suffix = 'created';
            } elseif ($action->action == ActionLoggerEnum::UPDATE) {
                $suffix = 'updated';
            } elseif ($action->action == ActionLoggerEnum::DELETE) {
                $suffix = 'deleted';
            } elseif ($action->action == ActionLoggerEnum::DESTROY) {
                $suffix = 'permanently deleted';
            } elseif ($action->action == ActionLoggerEnum::RESTORE) {
                $suffix = 'restored';
            } elseif ($action->action == ActionLoggerEnum::DUPLICATE) {
                $suffix = 'duplicated';
            }

            $description = $action->moduleTitle.' '.$action->nameOfTheData.' '.$suffix;
        }

        $request = request();

        $insert = [
            'action' => $action->action,
            'refId' => $action->id,
            'token' => $action->token,
            'description' => $description ?? $action->description,
            'data' => $action->data ? \json_encode($action->data) : null,
            'module' => $action->moduleTitle,
            'route' => $action->route,
            'ipAddress' => $request->ip(),
            'userAgent' => $request->userAgent(),
            'createdByName' => Auth::user()->name,
        ];

        (new DataModel())->db('action_logs', 'id')->add($insert);
    }

    private static function getToken(BluePrint $bluePrint, $result)
    {
        $model = $bluePrint->getForm()->getModel();

        return $model->isToken() ? ($result->{$model->getTokenCol()} ?? null) : null;
    }
}
