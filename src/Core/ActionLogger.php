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
        if ( ! $bluePrint->form->isLogAction())
            return;

        $action = 'create';

        $data = [];
        foreach ($bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

        $token = $bluePrint->form->getModel()->getLastToken();
        self::insert($bluePrint, $action, $refId, $data, $token);
    }

    public static function duplicate(BluePrint $bluePrint, $refId, $result, $oldData)
    {
        if ( ! $bluePrint->form->isLogAction())
            return;

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
            'token' => $token
        ];

        $token = $bluePrint->form->getModel()->getLastToken();
        self::insert($bluePrint, $action, $refId, $data, $token);
    }

    public static function update(BluePrint $bluePrint, $refId, $oldData, $newData)
    {
        if ( ! $bluePrint->form->isLogAction())
            return;

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
            if ($value)
                $data['data'][$input->getLabel()] = $value;
        }

        self::insert($bluePrint, $action, $refId, $data, self::getToken($bluePrint, $oldData));
    }

    public static function delete(BluePrint $bluePrint, $refId, $oldData)
    {
        if ( ! $bluePrint->form->isLogAction())
            return;

        $action = 'delete';

        self::insert($bluePrint, $action, $refId, null, self::getToken($bluePrint, $oldData));
    }

    public static function destroy(BluePrint $bluePrint, $refId, $oldData)
    {
        if ( ! $bluePrint->form->isLogAction())
            return;

        $action = 'destroy';

        $data = [];
        foreach ($bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            $input->setValue($oldData->{$input->getDbField()} ?? '');
            $data['data'][$input->getLabel()] = $input->getLoggerValue($action);
        }

        self::insert($bluePrint, $action, $refId, $data, self::getToken($bluePrint, $oldData));
    }

    public static function restore(BluePrint $bluePrint, $refId, $oldData)
    {
        if ( ! $bluePrint->form->isLogAction())
            return;

        $action = 'restore';

        self::insert($bluePrint, $action, $refId, null, self::getToken($bluePrint, $oldData));
    }

    private static function insert(BluePrint $bluePrint, string $action, ?string $refId, $data, ?string $token = null)
    {
        $insert = [
            'module' => $bluePrint->getForm()->getResource()->title,
            'route' => $bluePrint->getForm()->getResource()->route,
            'action' => $action,
            'refId' => $refId,
            'token' => $token,
            'data' => $data ? \json_encode($data) : null,
            'actionBy' => Auth::user()->userId,
            'actionByName' => Auth::user()->name,
            'actionAt' => \date('Y-m-d H:i:s'),
        ];

        DB::table('action_logs')->insert($insert);
    }

    private static function getToken(BluePrint $bluePrint, $result)
    {
        $model = $bluePrint->form->getModel();
        return $model->isToken() ? ($result->{$model->getTokenCol()} ?? null) : null;
    }
}