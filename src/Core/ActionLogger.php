<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\Common\IEncryptable;
use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Support\Facades\DB;

// TODO: Multiple Logger
// TODO: Keep deleted files and images

class ActionLogger
{
    public static function create(BluePrint $bluePrint, $refId)
    {
        if (! $bluePrint->form->isLogAction()) {
            return;
        }

        $action = 'create';

        $data = [];
        foreach ($bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
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
            'token' => $bluePrint->form->getModel()->getLastToken(),
            'description' => $description,
            'data' => $data,
        ];
        self::insert($bluePrint, $request);
    }

    public static function duplicate(BluePrint $bluePrint, $refId, $result, $oldData)
    {
        if (! $bluePrint->form->isLogAction()) {
            return;
        }

        $action = 'duplicate';

        $data = [];
        foreach ($bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            $input->setValue($result->{$input->getDbField()} ?? '');
            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

        $model = $bluePrint->form->getModel();
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
            'token' => $bluePrint->form->getModel()->getLastToken(),
            'description' => $description,
            'data' => $data,
        ];
        self::insert($bluePrint, $request);
    }

    public static function update(BluePrint $bluePrint, $refId, $oldData, $newData)
    {
        if (! $bluePrint->form->isLogAction()) {
            return;
        }

        $action = 'update';

        $newData = (object) $newData;

        $data = [];
        foreach ($bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
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
        ];
        self::insert($bluePrint, $request);
    }

    public static function delete(BluePrint $bluePrint, $refId, $oldData)
    {
        if (! $bluePrint->form->isLogAction()) {
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
        ];
        self::insert($bluePrint, $request);
    }

    public static function destroy(BluePrint $bluePrint, $refId, $oldData)
    {
        if (! $bluePrint->form->isLogAction()) {
            return;
        }

        $action = 'destroy';

        $data = [];
        foreach ($bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
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
        ];
        self::insert($bluePrint, $request);
    }

    public static function restore(BluePrint $bluePrint, $refId, $oldData)
    {
        if (! $bluePrint->form->isLogAction()) {
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
        ];
        self::insert($bluePrint, $request);
    }

    private static function insert(BluePrint $bluePrint, $data)
    {
        $insert = [
            'module' => $bluePrint->getForm()->getResource()->title,
            'route' => $bluePrint->getForm()->getResource()->route,
            'actionBy' => Auth::user()->userId,
            'actionByName' => Auth::user()->name,
            'actionAt' => \date('Y-m-d H:i:s'),
        ];

        $data['data'] = $data['data'] ? \json_encode($data['data']) : null;
        $insert = array_merge($insert, $data);

        DB::table('action_logs')->insert($insert);
    }

    private static function getToken(BluePrint $bluePrint, $result)
    {
        $model = $bluePrint->form->getModel();

        return $model->isToken() ? ($result->{$model->getTokenCol()} ?? null) : null;
    }
}
