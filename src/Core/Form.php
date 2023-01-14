<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\Common\InputType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

abstract class FormStatus
{
    public const Create = 1;
    public const Store = 2;
    public const Edit = 3;
    public const Update = 4;
    public const Delete = 5;
    public const Destroy = 6;
}

class Form
{
    private $bluePrint;
    private $resource;
    private $model;

    private int $formStatus = 0;

    private $request;

    private $editId;

    private $url = '';
    private $queryString = '';

    private $resultData = null;
    private $postData = [];
    private $updatePostData = [];
    private $oldData = null;

    private $crud = null;
    private $options = null;

    private bool $isLogAction = true;

    private $callbackValidation = null;
    private $uniqueColumns = null;

    private $tableMetaColumns = [
        'updatedBy' => 'updatedBy',
        'updatedAt' => 'updatedAt',
        'createdBy' => 'createdBy',
        'createdAt' => 'createdAt',
        'deletedBy' => 'deletedBy',
        'deletedAt' => 'deletedAt',
    ];

    public function __construct($resource, BluePrint $bluePrint, DataModel $model)
    {
        $this->resource = $resource;
        $this->bluePrint = $bluePrint;
        $this->model = $model;

        $this->options = new \stdClass();

        $this->request = request();
        $this->url = config('form-tool.adminURL').'/'.$this->resource->route;
        $this->queryString = '?'.$this->request->getQueryString();

        $this->isLogAction = \config('form-tool.isLogActions', true);
    }

    public function init($except = null)
    {
        $method = $this->request->method();

        if ($except) {
            $except = array_map(function ($val) {
                return \strtoupper($val);
            }, $except);
        }

        if ('POST' == $method && \strtoupper($this->request->post('method')) == 'CREATE'
            && (! $except || ! in_array('STORE', $except))) {
            return $this->store();
        } elseif ('PUT' == $method && (! $except || ! in_array('UPDATE', $except))) {
            return $this->update();
        } elseif ('DELETE' == $method && (! $except || ! in_array('DESTROY', $except))) {
            return $this->delete();
        }
    }

    //region Options

    public function doNotSave($fields): Crud
    {
        $this->options->doNotSave = [];
        if (\is_string($fields)) {
            $this->options->doNotSave[] = $fields;
        } elseif (is_array($fields)) {
            $this->options->doNotSave = $fields;
        }

        return $this->crud;
    }

    public function saveOnly($fields): Crud
    {
        $this->options->saveOnly = [];
        if (\is_string($fields)) {
            $this->options->saveOnly[] = $fields;
        } elseif (is_array($fields)) {
            $this->options->saveOnly = $fields;
        }

        return $this->crud;
    }

    public function actionLog(bool $flag = true): Crud
    {
        $this->isLogAction = $flag;

        return $this->crud;
    }

    public function heroField(string $field): Crud
    {
        $this->bluePrint->setHeroField($field);

        return $this->crud;
    }

    public function callbackValidation(\Closure $callbackValidation)
    {
        $this->callbackValidation = $callbackValidation;

        return $this->crud;
    }

    public function unique(array $columns)
    {
        $this->uniqueColumns = [];
        $columns = array_values($columns);
        foreach ($columns as $col) {
            if (! $this->bluePrint->getInputTypeByDbField($col)) {
                throw new \InvalidArgumentException(sprintf('Field "%s" not found for unique validation', $col));
            }
        }

        $this->uniqueColumns = $columns;

        return $this->crud;
    }

    //endregion

    //region GenerateForm

    public function create()
    {
        $data = new \stdClass();

        $data->fields = new \stdClass();

        foreach ($this->bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                $data->fields->{$input->getKey()} = $this->getMultipleTable($input);
            } else {
                // Let's modify value before if needed like for decryption
                // We wil not modify anything on create as default values must be set as it is
                if ($this->formStatus == FormStatus::Edit) {
                    $input->setValue($input->getValue());
                }

                $data->fields->{$input->getDbField()} = $input->getHTML();
            }
        }

        $data->isEdit = $this->formStatus == FormStatus::Edit;

        $url = URL::to(config('form-tool.adminURL').'/'.$this->resource->route);
        $data->action = $data->cancel = $url.$this->queryString;

        if ($data->isEdit) {
            $editId = $this->model->isToken() ?
                $this->resultData->{$this->model->getTokenCol()} :
                ($this->resultData->{$this->model->getPrimaryId()} ?? null);

            if ($editId) {
                $data->action = $url.'/'.$editId.$this->queryString;
            } else {
                $data->action = $url.$this->queryString;
            }
        }

        return $data;
    }

    private function getMultipleTable($model)
    {
        $key = $model->getFullKey();
        $keyName = \str_replace(['[', ']'], '-', $key);

        // Getting the template at the beginning will make sure that it will not contain any values of the field
        $template = $this->getTableRow($model, $key, $keyName);

        $tableData = new \stdClass();
        $tableData->label = $model->label;
        $tableData->classes = '';
        $tableData->id = $keyName;
        $tableData->required = $model->getRequired();
        $tableData->header = [];
        $tableData->rows = '';
        $tableData->totalColumns = 0;

        // TODO: Add help & other attributes in multiple table
        //$tableData->help = $model->help;

        if ($model->isOrderable()) {
            $tableData->classes .= ' table-orderable';
        }

        if ($model->isConfirmBeforeDelete()) {
            $tableData->classes .= ' confirm-delete';
        }

        $totalCols = 0;
        foreach ($model->getList() as $field) {
            if (! $field instanceof BluePrint) {
                if ($field->getType() != InputType::HIDDEN) {
                    $tableData->header[] = $field->getLabel();
                    $totalCols++;
                }
            } else {
                $tableData->header[] = '';
                $totalCols++;
            }
        }
        $tableData->header[] = '';

        // TODO: Need to work for only one file field
        // Check if any session data exists
        $totalDataInSession = 0;
        if (count($model->getList()) > 0) {
            $field = $model->getList()[0]->getDbField();
            $val = old($key);
            if ($val && \is_array($val)) {
                $totalDataInSession = \count($val);
            }
        }

        // Let's get data for multiple fields if its Edit
        $result = null;
        $totalRowsInEdit = 0;

        // TODO: Need to check if validation failed without $totalDataInSession
        if (! $totalDataInSession && $this->formStatus == FormStatus::Edit) {
            $dbModel = $model->getModel();
            if ($dbModel) {
                if ($dbModel instanceof \stdClass) {
                    $where = [$dbModel->foreignKey => $this->editId];

                    $query = DB::table($dbModel->table)->where($where);
                    if ($dbModel->orderBy) {
                        $query->orderBy($dbModel->orderBy, 'asc');
                    } elseif ($model->getOrderByColumn()) {
                        $query->orderBy($model->getOrderByColumn());
                    } else {
                        $query->orderBy($dbModel->id, 'asc');
                    }

                    $result = $query->get();
                } else {
                    if ($model->getOrderByColumn()) {
                        $dbModel::$orderBy = $model->getOrderByColumn();
                    }

                    $where = [$dbModel::$foreignKey => $this->editId];
                    $result = $dbModel::getWhere($where);
                }
            } elseif (isset($this->resultData->{$key})) {
                $result = \json_decode($this->resultData->{$key});
            }

            if ($result) {
                $totalRowsInEdit = \count($result);

                $i = 0;
                foreach ($result as $row) {
                    foreach ($model->getList() as $field) {
                        $field->setIndex($model->getKey(), $i);

                        $field->setValue($row->{$field->getDbField()} ?? null);
                    }

                    $tableData->rows .= $this->getTableRow($model, $key, $keyName, $i++);
                }
            }
        }

        $appendCount = 0;
        if ($model->getRequired() > 0) {
            // Check if the required items is greater than the items already saved
            if ($result) {
                $appendCount = $model->getRequired() - $totalRowsInEdit;
            } elseif (! $result || $this->formStatus == FormStatus::Create) {
                $appendCount = $model->getRequired();
            }
        }

        if ($appendCount < $totalDataInSession) {
            $appendCount = $totalDataInSession;
        }

        for ($i = 0; $i < $appendCount; $i++) {
            // Sending index will get the field value if there any in the session
            $tableData->rows .= $this->getTableRow($model, $key, $keyName, $i + $totalRowsInEdit);
        }

        $tableData->totalColumns = ++$totalCols;

        Doc::addJs('template["'.$keyName.'"]=`'.$template.'`', $keyName);

        $data['table'] = $tableData;

        return \view('form-tool::form.multiple_table', $data)->render();
    }

    private function getTableRow($model, $key, $keyName, $index = -1)
    {
        // If $index is -1 means we just need the template
        if ($index == -1) {
            $index = '{__index}';
        }

        $rowData = new \stdClass();
        $rowData->id = $key.'-row-'.$index;
        $rowData->isOrderable = $model->isOrderable();
        $rowData->hidden = '';
        $rowData->columns = '';

        foreach ($model->getList() as $field) {
            if ($field instanceof BluePrint) {
                $rowData->columns .= '<td>'.$this->getMultipleTable($field).'</td>';
            } else {
                // Check if there is any value in the session
                $oldValue = old($key.'.'.$index.'.'.$field->getDbField());

                // Let's modify value before if needed like for decryption
                $field->setValue($field->getValue());

                if ($field->getType() == InputType::HIDDEN) {
                    $rowData->hidden .= $field->getHTMLMultiple($key, $index, $oldValue);
                } else {
                    $rowData->columns .= '<td>'.$field->getHTMLMultiple($key, $index, $oldValue).'</td>';
                }
            }
        }

        $data['row'] = $rowData;

        return \view('form-tool::form.multiple_table_row', $data)->render();
    }

    //endregion

    public function edit($id = null)
    {
        $this->formStatus = FormStatus::Edit;

        $this->resultData = null;
        if ($this->crud->isDefaultFormat()) {
            if (! $id) {
                $url = $this->request->getRequestUri();

                $matches = [];
                $t = \preg_match('/'.$this->resource->route.'\/([^\/]*)\/edit/', $url, $matches);
                if (\count($matches) > 1) {
                    $id = $matches[1];
                } else {
                    throw new \InvalidArgumentException('Could not fetch "id"! Pass $id manually as parameter.');
                }
            }

            $this->resultData = $this->model->getOne($id);
            if (! $this->resultData) {
                abort(404);
            }

            // Get the primary id as $id can be token
            $this->editId = $this->resultData->{$this->model->getPrimaryId()};
        } else {
            // We are into key value pair format, let's format data
            $result = $this->model->getWhere(['groupName' => $this->crud->getGroupName()]);

            $this->resultData = new \stdClass();
            foreach ($result as $row) {
                $this->resultData->{$row->key} = $row->value;
            }
        }

        foreach ($this->bluePrint->getList() as $input) {
            if (! $input instanceof BluePrint) {
                $input->setValue($this->resultData->{$input->getDbField()} ?? null);
            }
        }
    }

    //region FormAction StoreAndUpdate

    public function store()
    {
        $this->formStatus = FormStatus::Store;

        $validate = $this->validate();
        if ($validate !== true) {
            return $validate;
        }

        $result = $this->createPostData();
        if ($result !== true) {
            return $result;
        }

        $insertId = $this->model->add($this->postData);

        if ($insertId) {
            $this->editId = $insertId;

            $this->afterSave();

            ActionLogger::create($this->bluePrint, $insertId);
        }

        return back()->with('success', 'Data added successfully!');
    }

    public function update($id = null, callable $callbackBeforeUpdate = null)
    {
        $this->formStatus = FormStatus::Update;

        if ($this->crud->isDefaultFormat()) {
            $id = $this->parseEditId($id);
        }

        $validate = $this->validate();
        if ($validate !== true) {
            return $validate;
        }

        $token = null;
        if ($this->crud->isDefaultFormat()) {
            if (! $this->oldData) {
                $this->oldData = $this->model->getOne($this->editId);
            }

            if (! $this->oldData) {
                return redirect($this->url.$this->queryString)->with(
                    'error',
                    'Something went wrong! Data not found, please try again!'
                );
            }

            $this->editId = $this->oldData->{$this->model->getPrimaryId()};
        } else {
            $result = $this->model->getWhere(['groupName' => $this->crud->getGroupName()]);
            $this->editId = -1;

            $this->oldData = new \stdClass();
            foreach ($result as $row) {
                $this->oldData->{$row->key} = $row->value;
            }
        }

        // TODO:
        // validations
        //      permission to update
        //      can update this row

        $result = $this->createPostData($this->editId);
        if ($result !== true) {
            return $result;
        }

        if ($this->crud->isDefaultFormat()) {
            if ($callbackBeforeUpdate) {
                $response = $callbackBeforeUpdate($this->postData);
                if ($response !== null) {
                    $this->postData = $response;
                }
            }

            $this->model->updateOne($this->editId, $this->postData, false);
        } else {
            $this->model->destroyWhere(['groupName' => $this->crud->getGroupName()]);

            $insert = [];
            foreach ($this->postData as $key => $value) {
                $insert[] = ['groupName' => $this->crud->getGroupName(), 'key' => $key, 'value' => $value];
            }

            if ($callbackBeforeUpdate) {
                $response = $callbackBeforeUpdate($insert);
                if ($response !== null) {
                    $insert = $response;
                }
            }

            $this->model->addMany($insert);
        }

        $this->afterSave();

        ActionLogger::update($this->bluePrint, $this->editId, $this->oldData, $this->postData);

        return redirect($this->url.$this->queryString)->with('success', 'Data updated successfully!');
    }

    private function afterSave()
    {
        if (! $this->editId) {
            return;
        }

        $result = null;
        if ($this->crud->isDefaultFormat()) {
            $result = $this->model->getOne($this->editId, false);
        } else {
            $data = $this->model->getWhere(['groupName' => $this->crud->getGroupName()]);

            $result = new \stdClass();
            foreach ($data as $row) {
                $result->{$row->key} = $row->value;
            }
        }

        foreach ($this->bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            if ($this->formStatus == FormStatus::Store) {
                $input->afterStore($result);
            } else {
                $input->afterUpdate($this->oldData, $result);
            }
        }

        $this->saveMultipleFields();
    }

    private function saveMultipleFields()
    {
        foreach ($this->bluePrint->getList() as $input) {
            if (! $input instanceof BluePrint) {
                continue;
            }

            $model = $input->getModel();

            $foreignKey = null;
            if ($model) {
                if ($model instanceof \stdClass) {
                    $foreignKey = $model->foreignKey;
                } else {
                    if (! isset($model::$foreignKey)) {
                        throw new \InvalidArgumentException('$foreignKey property not defined at '.$model);
                    }

                    $foreignKey = $model::$foreignKey;
                }
            }

            $postData = $this->request->post($input->getKey());

            // Some field don't send data into the post like file
            // And it's a buggy if we only have only file field in multiple
            // So let's create an array of null if have any data in our file field
            // count() help to determine on edit with some data in post and some new upload
            if ($postData == null || count($input->getList()) == 1) {
                foreach ($input->getList() as $field) {
                    if ($this->request->file($input->getKey())) {
                        if ($postData) {
                            $postData = array_merge(
                                $postData,
                                array_fill(0, \count($this->request->file($input->getKey())), null)
                            );
                        } else {
                            $postData = array_fill(0, \count($this->request->file($input->getKey())), null);
                        }
                        break;
                    }
                }
            }

            $data = [];
            if ($postData && \is_array($postData)) {
                foreach ($postData as $index => $row) {
                    $dataRow = [];
                    foreach ($input->getList() as $field) {
                        $field->setIndex($input->getKey(), $index);

                        $dbField = $field->getDbField();

                        // If we don't have a postdata for an field like for an optional file field
                        $dataRow[$dbField] = $row[$dbField] ?? null;

                        $field->setValue($dataRow[$dbField]);

                        $response = null;
                        if ($this->formStatus == FormStatus::Store) {
                            $response = $field->beforeStore((object) $row);
                        } else {
                            $response = $field->beforeUpdate((object) $row, (object) $row);
                        }

                        if ($response !== null) {
                            $dataRow[$dbField] = $response;
                        }

                        if (! $dataRow[$dbField] && $field->getDefaultValue() !== null) {
                            $dataRow[$dbField] = $field->getDefaultValue();
                        }

                        if ($this->formStatus == FormStatus::Store) {
                            $field->afterStore((object) $dataRow);
                        } else {
                            $field->afterUpdate((object) $row, (object) $dataRow);
                        }
                    }

                    if ($foreignKey) {
                        $dataRow[$foreignKey] = $this->editId;
                    }

                    $data[] = $dataRow;
                }
            }

            if ($model) {
                $where = [$foreignKey => $this->editId];
                if ($model instanceof \stdClass) {
                    DB::table($model->table)->where($where)->delete();
                    if (\count($data)) {
                        DB::table($model->table)->insert($data);
                    }
                } else {
                    $model::deleteWhere($where);
                    if (\count($data)) {
                        $model::addMany($data);
                    }
                }
            } else {
                $this->model->updateOne($this->editId, [$input->getKey() => json_encode($data)]);
            }
        }
    }

    private function validate()
    {
        $validationType = $this->formStatus == FormStatus::Store ? 'store' : 'update';

        $rules = $messages = $labels = $merge = [];
        foreach ($this->bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            $newValue = $input->beforeValidation($this->request->post($input->getDbField()));
            if ($newValue !== null) {
                $merge[$input->getDbField()] = $newValue;
            }

            $rules[$input->getDbField()] = $input->getValidations($validationType);

            $messages = array_merge($messages, $input->getValidationMessages());

            $labels[$input->getDbField()] = $input->getLabel();
        }

        if ($merge) {
            $this->request->merge($merge);
        }

        $validator = \Validator::make($this->request->all(), $rules, $messages, $labels);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        if ($this->uniqueColumns) {
            $where = [];
            $combination = [];
            $alias = $this->model->getAlias().'.';
            foreach ($this->uniqueColumns as $column) {
                $input = $this->bluePrint->getInputTypeByDbField($column);
                $value = $this->request->post($column) ?? $input->getDefaultValue();

                $where[] = [$alias.$column => $value];
                $combination[] = $input->getNiceValue($value) ?: $input->getDefaultValue();
            }

            if ($this->formStatus == FormStatus::Update) {
                $where[] = function ($query) use ($alias) {
                    $query->where($alias.$this->model->getPrimaryId(), '!=', $this->editId);
                };
            }

            $count = $this->model->countWhere($where);
            if ($count) {
                return back()->with('error', \sprintf(
                    'The combination of "%s" is already exist!',
                    \implode(', ', array_values($combination))
                ))->withInput();
            }
        }

        if ($this->callbackValidation) {
            $callbackValidation = $this->callbackValidation;
            $response = $callbackValidation($this->request, $validationType);
            if ($response !== true) {
                if (\is_string($response)) {
                    return back()->with('error', $response)->withInput();
                } elseif ($response instanceof \Illuminate\Http\RedirectResponse) {
                    return $response->send();
                } else {
                    return back()->with(
                        'error',
                        'Validation failed: NO_CUSTOM_MESSAGE_RETURNED_FROM_CALLBACK_VALIDATION'
                    )->withInput();
                }
            }
        }

        $this->postData = $validator->validated();

        return true;
    }

    private function createPostData($id = null)
    {
        $postData = $this->postData;
        $this->postData = [];

        if ($this->formStatus == FormStatus::Update) {
            $id = $this->parseEditId($id);

            if (! $this->oldData) {
                $this->oldData = $this->model->getOne($this->editId);
            }
        }

        // I think we should not remove the meta data like dates and updatedby
        // Remove if there is any extra fields that are not needed

        foreach ($this->bluePrint->getList() as $input) {
            $dbField = $input instanceof BluePrint ? $input->getKey() : $input->getDbField();

            // Check if we don't want to save or only save to prevent further process of the field
            if (isset($this->options->doNotSave) && $this->options->doNotSave) {
                if (\in_array($dbField, $this->options->doNotSave)) {
                    continue;
                }
            }
            if (isset($this->options->saveOnly) && $this->options->saveOnly) {
                if (! \in_array($dbField, $this->options->saveOnly)) {
                    continue;
                }
            }

            // If we have custom data to update, then we will update and prevent our further process
            if (isset($this->updatePostData[$dbField])) {
                $this->postData[$dbField] = $this->updatePostData[$dbField];

                continue;
            }

            if ($input instanceof BluePrint) {
                if (! $input->getModel()) {
                    $this->postData[$input->getKey()] = \json_encode($this->request[$input->getKey()]);
                }

                continue;
            }

            // If we don't have a postdata for an field like for an optional file field
            $this->postData[$dbField] = $postData[$dbField] ?? null;

            $input->setValue($this->postData[$dbField]);

            $response = null;
            if ($this->formStatus == FormStatus::Store) {
                $response = $input->beforeStore((object) $this->postData);
            } else {
                $response = $input->beforeUpdate($this->oldData, (object) $this->postData);
            }

            if ($response !== null) {
                $this->postData[$dbField] = $response;
            }

            if (! $this->postData[$dbField] && $input->getDefaultValue() !== null) {
                $this->postData[$dbField] = $input->getDefaultValue();
            }
        }

        return true;
    }

    private function parseEditId($id)
    {
        if ($id) {
            $this->editId = $id;

            return $id;
        }

        $url = $this->request->getRequestUri();

        $matches = [];
        $t = \preg_match('/'.$this->resource->route.'\/([^\/\?$]*)/', $url, $matches);
        if (\count($matches) > 1) {
            $this->editId = $matches[1];

            return $this->editId;
        }

        throw new \InvalidArgumentException('Could not fetch "id"! Pass $id manually as parameter.');
    }

    //endregion

    //region FormAction DeleteAndDestroy

    public function delete($id = null)
    {
        if (! $this->crud->isSoftDelete()) {
            return $this->destroy($id);
        }

        $this->formStatus = FormStatus::Delete;

        $id = $this->parseEditId($id);

        $result = $this->model->getOne($id);
        if (! $result) {
            return redirect($this->url.$this->queryString)->with(
                'error',
                'Something went wrong! Data not found, please try again!'
            );
        }

        $pId = $id;
        if ($this->model->isToken()) {
            $pId = $result->{$this->model->getPrimaryId()} ?? null;
        }

        $response = $this->checkForeignKeyRestriction($pId, $result);
        if ($response !== true) {
            return $response;
        }

        $affected = $this->model->updateDelete($id);

        ActionLogger::delete($this->bluePrint, $pId, $result);

        return redirect($this->url.$this->queryString)->with('success', 'Data deleted successfully!');
    }

    public function destroy($id = null)
    {
        $this->formStatus = FormStatus::Destroy;

        $id = $this->parseEditId($id);

        $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
        $deletedAt = $metaColumns['deletedAt'] ?? 'deletedAt';

        // TODO:
        // validations
        //      permission to delete
        //      can delete this row

        // We can't use getOne, as we need to fetch deleted item
        $idCol = $this->model->isToken() ? $this->model->getTokenCol() : $this->model->getPrimaryId();
        $result = $this->model->getWhereOne([$idCol => $id]);
        if (! $result) {
            return redirect($this->url.$this->queryString)->with(
                'error',
                'Something went wrong! Data not found, please try again!'
            );
        }

        $pId = $id;
        if ($this->model->isToken()) {
            $pId = $result->{$this->model->getPrimaryId()} ?? null;
        }

        $response = $this->checkForeignKeyRestriction($pId, $result);
        if ($response !== true) {
            return $response;
        }

        if ($this->crud->isSoftDelete()) {
            if (! \property_exists($result, $deletedAt)) {
                throw new \Exception('Column "deletedAt" not found!');
            }

            if ($result->{$deletedAt} === null) {
                return redirect($this->url.$this->queryString)->with(
                    'error',
                    'Soft delete is enabled for this module. You need to mark as delete first
                        then only you can delete it permanently!'
                );
            }
        }

        foreach ($this->bluePrint->getList() as $field) {
            if ($field instanceof BluePrint) {
                // TODO: Still now not needed
            } else {
                $field->beforeDestroy($result);
            }
        }

        $affected = $this->model->deleteOne($id);

        if ($affected > 0) {
            foreach ($this->bluePrint->getList() as $input) {
                if ($input instanceof BluePrint) {
                    // Let's delete the file and image of sub tables, and data
                    $childResult = [];
                    if ($input->getModel()) {
                        $model = $input->getModel();

                        $foreignKey = null;
                        if ($model instanceof \stdClass) {
                            $foreignKey = $model->foreignKey;
                        } else {
                            if (! isset($model::$foreignKey)) {
                                throw new \InvalidArgumentException('$foreignKey property not defined at '.$model);
                            }

                            $foreignKey = $model::$foreignKey;
                        }

                        $where = [$foreignKey => $pId];
                        $childResult = DB::table($model->table)->where($where)->orderBy($model->id, 'asc')->get();

                        if ($model instanceof \stdClass) {
                            DB::table($model->table)->where($where)->delete();
                        } else {
                            $model::deleteWhere($where);
                        }
                    } else {
                        $childResult = \json_decode($result->{$input->getKey()});
                    }

                    foreach ($childResult as $row) {
                        foreach ($input->getList() as $childInput) {
                            $childInput->afterDestroy($row);
                        }
                    }
                } else {
                    $input->afterDestroy($result);
                }
            }

            ActionLogger::destroy($this->bluePrint, $pId, $result);

            return redirect($this->url.$this->queryString)->with('success', 'Data permanently deleted successfully!');
        }

        return redirect($this->url.$this->queryString)->with(
            'error',
            'Something went wrong! Data not deleted fully, please contact Support Administartor.'
        );
    }

    private function checkForeignKeyRestriction($id, $dataToDelete)
    {
        if (! \config('form-tool.isPreventForeignKeyDelete', true)) {
            return true;
        }

        $dataCount = [];
        $totalCount = 0;

        $totalReferencesToDisplay = 10;
        $totalReferencesToFetch = 100;

        $result = DB::table('cruds')->get();
        if (\count($result) > 0) {
            foreach ($result as $row) {
                $data = \json_decode($row->data);
                if (! isset($data->foreignKey)) {
                    continue;
                }

                foreach ($data->foreignKey as $option) {
                    if ($option->table == $this->model->getTableName()) {
                        $resultData = DB::table($data->main->table)->where($option->column, $id)
                            ->limit($totalReferencesToFetch)->get();

                        $count = \count($resultData);
                        if ($count > 0) {
                            $dataCount[] = [
                                'count' => $count,
                                'title' => $data->main->title,
                                'route' => $row->route,
                                'label' => $option->label,
                                'id' => $data->main->id,
                                'result' => $resultData,
                            ];
                            $totalCount += $count;

                            // Let's break, we will not fetch more than $totalReferencesToFetch items
                            if ($totalCount >= $totalReferencesToFetch) {
                                break;
                            }
                        }
                    }
                }

                // Let's break, we will not fetch more than $totalReferencesToFetch items
                if ($totalCount >= $totalReferencesToFetch) {
                    break;
                }
            }
        }

        if ($dataCount) {
            $displayId = $id;
            if ($this->model->isToken()) {
                $displayId = $dataToDelete->{$this->model->getTokenCol()} ?? null;
            }

            $msg = '';
            $heroField = $this->bluePrint->getHeroField();
            if ($heroField && isset($dataToDelete->{$heroField}) && $dataToDelete->{$heroField}) {
                $msg .= 'Data: <b>"'.$dataToDelete->{$heroField}.'" (ID: '.$displayId.')</b> ';
            } else {
                $msg .= '<b>ID: '.$displayId.'</b> ';
            }

            $msg .= \sprintf(
                'is linked with <b>%s</b> data. You need to destroy all the linked data first to delete this item. ',
                $totalCount
            );

            if ($totalCount > 10) {
                $msg .= 'Below are some of the data which are linked to this item:';
            } else {
                $msg .= 'Below data are linked to this item:';
            }

            $metaColumns = \config('form-tool.table_meta_columns', $this->tableMetaColumns);
            $deletedAt = $metaColumns['deletedAt'] ?? 'deletedAt';

            $msg .= '<br /><ul>';
            $i = 0;
            foreach ($dataCount as $result) {
                if ($result['label']) {
                    $msg .= \sprintf(
                        '<li>%s data of <b>%s</b> in field "%s"</li>',
                        $result['count'],
                        $result['title'],
                        $result['label']
                    );
                } else {
                    $msg .= \sprintf(
                        '<li>%s data of <b>%s</b></li>',
                        $result['count'],
                        $result['title']
                    );
                }

                $url = URL::to(\config('form-tool.adminURL').'/'.$result['route']);
                $hasEditPermission = Guard::hasEdit($result['route']);
                $hasDestroyPermission = Guard::hasDestroy($result['route']);

                $msg .= '<ul>';
                foreach ($result['result'] as $row) {
                    $id = $row->{$result['id']} ?? null;

                    if ($id) {
                        $newMsg = '<li>ID: '.$id.'</li>';

                        $isDeleted = $row->{$deletedAt} ?? null;
                        if ($isDeleted) {
                            if ($hasDestroyPermission) {
                                $newMsg = \sprintf(
                                    '<li>ID: <a href="%s?id=%s&quick_status=trash" target="_blank">%s &nbsp
                                        <i class="fa fa-external-link"></i></a></li>',
                                    $url,
                                    $id,
                                    $id
                                );
                            }
                        } elseif ($hasEditPermission) {
                            $newMsg = \sprintf(
                                '<li>ID: <a href="%s?id=%s" target="_blank">%s &nbsp <i class="fa fa-external-link">
                                    </i></a></li>',
                                $url,
                                $id,
                                $id
                            );
                        }

                        $msg .= $newMsg;
                    } else {
                        $msg .= "<li>ID: <i>{$result['id']} column not found</i></li>";
                    }

                    $i++;
                    if ($i >= $totalReferencesToDisplay) {
                        break;
                    }
                }
                $msg .= '</ul>';

                if ($i >= $totalReferencesToDisplay) {
                    if ($totalCount == $totalReferencesToFetch) {
                        $msg .= '<li><i>More than '.$totalReferencesToFetch.'+ items...</i></li>';
                    } else {
                        $msg .= '<li><i>+'.($totalCount - $totalReferencesToDisplay).' more item(s)...</i></li>';
                    }
                    break;
                }
            }
            $msg .= '</ul>';

            return redirect($this->url.$this->queryString)->with('error', $msg);
        }

        return true;
    }

    //endregion

    //region GetterSetter

    public function isUpdate()
    {
        return $this->formStatus == FormStatus::Update;
    }

    public function getPostData()
    {
        if ($this->postData) {
            return $this->postData;
        }

        return $this->request->post();
    }

    public function updatePostData($data)
    {
        $this->updatePostData = $data;
    }

    public function getModel()
    {
        return $this->model;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getId()
    {
        if (! $this->editId) {
            $this->parseEditId();
        }

        return $this->editId;
    }

    public function getEditData()
    {
        return $this->resultData;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getFullUrl()
    {
        return $this->url.$this->queryString;
    }

    public function setCrud(Crud $crud)
    {
        $this->crud = $crud;
    }

    public function getCrud()
    {
        return $this->crud;
    }

    public function isLogAction()
    {
        return $this->isLogAction;
    }

    //endregion
}
