<?php

namespace Biswadeep\FormTool\Core;

use Biswadeep\FormTool\Core\InputTypes\InputType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

abstract class FormStatus
{
    public const Create = 1;
    public const Store = 2;
    public const Edit = 3;
    public const Update = 4;
    public const Destroy = 4;
}

class Form
{
    private $_bluePrint;
    private $_resource;
    private $_model;

    private int $formStatus = 0;

    private $_request;

    private $_editId;

    private $_url = '';

    private $resultData = null;
    private $postData = null;
    private $oldData = null;

    private $crud = null;
    private $options = null;

    public function __construct($resource, BluePrint $bluePrint = null, $model = null)
    {
        $this->options = new \stdClass();

        $this->_resource = $resource;
        $this->_model = $model;

        if ($bluePrint) {
            $this->_bluePrint = $bluePrint;
        } else {
            $this->_bluePrint = BluePrint::getInstance();
        }

        $this->_bluePrint->form = $this;

        $this->_url = config('form-tool.adminURL').'/'.$this->_resource->route;
        $this->_request = request();
    }

    public function init()
    {
        $method = $this->_request->method();

        if ('POST' == $method) {
            return $this->store();
        } elseif ('PUT' == $method) {
            return $this->update();
        } elseif ('DELETE' == $method) {
            return $this->destroy();
        } elseif (strpos($this->_request->getRequestUri(), '/edit')) {
            return $this->edit();
        }
    }

    //region Options

    public function doNotSave($fields)
    {
        $this->options->doNotSave = [];
        if (\is_string($fields)) {
            $this->options->doNotSave[] = $fields;
        } elseif (is_array($fields)) {
            $this->options->doNotSave = $fields;
        }

        return $this->crud;
    }

    public function saveOnly($fields)
    {
        $this->options->saveOnly = [];
        if (\is_string($fields)) {
            $this->options->saveOnly[] = $fields;
        } elseif (is_array($fields)) {
            $this->options->saveOnly = $fields;
        }

        return $this->crud;
    }

    //endregion

    //region GenerateForm

    public function getHTMLForm()
    {
        $data['inputs'] = '';
        foreach ($this->_bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                $data['inputs'] .= '<div class="form-group"><label>'.$input->label.'</label>';
                $data['inputs'] .= $this->getMultipleFields($input);
                $data['inputs'] .= '</div>';
            } else {
                $data['inputs'] .= $input->getHTML();
            }
        }

        $isEdit = $this->formStatus == FormStatus::Edit;

        $data['isEdit'] = $isEdit;
        if ($isEdit) {
            $data['action'] = config('form-tool.adminURL').'/'.$this->_resource->route.'/'.$this->_editId;
        } else {
            $data['action'] = config('form-tool.adminURL').'/'.$this->_resource->route;
        }

        return view('form-tool::crud.components.form', $data);
    }

    private function getMultipleFields($model)
    {
        $key = $model->getFullKey();
        $keyName = \str_replace(['[', ']'], '-', $key);

        // Getting the template at the beginning will make sure that it will not contain any values of the field
        $template = $this->getTemplate($model, $key, $keyName);

        $classes = '';
        if ($model->isSortable()) {
            $classes .= ' table-sortable';
        }

        if ($model->isConfirmBeforeDelete()) {
            $classes .= ' confirm-delete';
        }

        $data = '<table class="table table-bordered'.$classes.'" id="'.$keyName.'" data-required="'.$model->getRequired().'"><thead>
        <tr class="active">';

        $totalCols = 0;
        foreach ($model->getList() as $field) {
            if (! $field instanceof BluePrint) {
                if ($field->getType() != InputType::Hidden) {
                    $data .= '<th>'.$field->getLabel().'</th>';
                    $totalCols++;
                }
            } else {
                $data .= '<th></th>';
                $totalCols++;
            }
        }

        $data .= '<th></th></tr></thead><tbody>';

        //Check if any session data exists
        $field = $model->getList()[0]->getDbField();
        $val = old($key.'.'.$field);
        $totalDataInSession = 0;
        if ($val && \is_array($val)) {
            $totalDataInSession = \count($val);
        }

        // Let's get data for multiple fields if its Edit
        $result = null;
        $totalRowsInEdit = 0;

        // TODO: Need to check if validation failed without $totalDataInSession
        if (! $totalDataInSession && $this->formStatus == FormStatus::Edit) {
            $dbModel = $model->getModel();
            if ($dbModel) {
                if ($dbModel instanceof \stdClass) {
                    $where = [$dbModel->foreignKey => $this->_editId];

                    $query = DB::table($dbModel->table)->where($where);
                    if ($dbModel->orderBy) {
                        $query->orderBy($dbModel->orderBy, 'asc');
                    } elseif ($model->getSortableField()) {
                        $query->orderBy($model->getSortableField());
                    }

                    $result = $query->get();
                } else {
                    if ($model->getSortableField()) {
                        $dbModel::$orderBy = $model->getSortableField();
                    }

                    $where = [$dbModel::$foreignKey => $this->_editId];
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
                        if (isset($row->{$field->getDbField()})) {
                            $field->setValue($row->{$field->getDbField()});
                        }
                    }

                    $data .= $this->getTemplate($model, $key, $keyName, $i++);
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
            $data .= $this->getTemplate($model, $key, $keyName, $i + $totalRowsInEdit);
        }

        $data .= '</tbody>
            <tfoot>
                <tr>
                    <td colspan="'.++$totalCols.'" class="text-right">
                        <a class="btn btn-primary btn-xs d_add"><i class="fa fa-plus"></i></a>
                    </td>
                </tr>
            </tfoot>
        </table>';

        Doc::addJs('template["'.$keyName.'"]=`'.$template.'`', $keyName);

        return $data;
    }

    private function getTemplate($model, $key, $keyName, $index = -1)
    {
        $template = '<tr class="d_block">';

        $hidden = '';
        foreach ($model->getList() as $field) {
            if ($field instanceof BluePrint) {
                $template .= '<td>'.$this->getMultipleFields($field).'</td>';
            } else {
                if ($field->getType() == InputType::Hidden) {
                    $hidden .= $field->getHTMLMultiple($key, $index);
                } else {
                    $template .= '<td>'.$field->getHTMLMultiple($key, $index).'</td>';
                }
            }
        }

        $template .= '<td colspan="2" class="text-right">';

        if ($model->isSortable()) {
            $template .= $hidden
                .'<a class="btn btn-default handle btn-xs" style="display:none"><i class="fa fa-arrows"></i></a>&nbsp; ';
        }

        $template .= '<a class="btn btn-default btn-xs text-danger d_remove" style="display:none"><i class="fa fa-times"></i></a>';
        $template .= '</td></tr>';

        return $template;
    }

    //endregion

    public function edit($id = false)
    {
        $this->formStatus = FormStatus::Edit;

        if (! $id) {
            $url = $this->_request->getRequestUri();

            $matches = [];
            $t = \preg_match('/'.$this->_resource->route.'\/([^\/]*)\/edit/', $url, $matches);
            if (\count($matches) > 1) {
                $id = $matches[1];
            } else {
                return redirect($this->_url)/*->action([get_class($this->_resource), 'index'])*/->with('error', 'Could not fetch "id"! Call edit manually.');
            }
        }

        $this->_editId = $id;

        $this->resultData = $this->_model->getOne($id);
        if (! $this->resultData) {
            abort(404);
        }

        foreach ($this->_bluePrint->getList() as $input) {
            if (! $input instanceof BluePrint && isset($this->resultData->{$input->getDbField()})) {
                $input->setValue($this->resultData->{$input->getDbField()});
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

        //dd($this->postData);

        $insertId = $this->_model->add($this->postData);

        if ($insertId) {
            $this->_editId = $insertId;

            $this->afterSave();

            // This will only execute if the method called by default not manually from the store
            /*if (\method_exists($this->_resource, 'store')) {
                $this->_resource->store($this->_request);
            }*/
        }

        return redirect($this->_url)->with('success', 'Data added successfully!');
    }

    public function update($id = null)
    {
        $this->formStatus = FormStatus::Update;

        if ($id) {
            $this->_editId = $id;
        } elseif (! $this->_editId) {
            $parse = $this->parseEditId();
            if (true !== $parse) {
                return $parse;
            }
        }

        $validate = $this->validate();
        if ($validate !== true) {
            return $validate;
        }

        if (! $this->oldData) {
            $this->oldData = $this->_model->getOne($this->_editId);
        }

        if (! $this->oldData) {
            throw new \Exception('Old data not found for ID: '.$this->_editId);
        }

        // TODO:
        // validations
        //      permission to update
        //      can update this row

        $result = $this->createPostData();
        if ($result !== true) {
            return $result;
        }

        $affected = $this->_model->updateOne($this->_editId, $this->postData);

        if ($affected > 0) {
            $this->afterSave();

            // This will only execute if the method called by default not manually from the store
            /*if (\method_exists($this->_resource, 'update')) {
                $this->_resource->update($this->_request, $this->_editId);
            }*/
        }

        return redirect($this->_url)->with('success', 'Data updated successfully!');
    }

    private function afterSave()
    {
        if (! $this->_editId) {
            return;
        }

        $result = $this->_model->getOne($this->_editId);
        foreach ($this->_bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            if ($this->formStatus == FormStatus::Store) {
                $response = $input->afterStore($result);
            } else {
                $response = $input->afterUpdate($this->oldData, $result);
            }
        }

        $this->saveMultipleFields();
    }

    private function saveMultipleFields()
    {
        foreach ($this->_bluePrint->getList() as $input) {
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

            $data = [];
            if ($this->_request->get($input->getKey()) && \is_array($this->_request->get($input->getKey()))) {
                foreach ($this->_request->get($input->getKey()) as $row) {
                    $dataRow = [];
                    foreach ($input->getList() as $field) {

                        // If we don't have a postdata for an field like for an optional file field
                        //$this->postData[$dbField] = $this->postData[$dbField] ?? null;

                        $dataRow[$field->getDbField()] = $row[$field->getDbField()] ?? null;
                    }

                    $dataRow[$foreignKey] = $this->_editId;

                    $data[] = $dataRow;
                }
            }

            $where = [$foreignKey => $this->_editId];
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
        }
    }

    private function validate()
    {
        $validationType = $this->formStatus == FormStatus::Store ? 'store' : 'update';

        $rules = $messages = $labels = $merge = [];
        foreach ($this->_bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            $newValue = $input->beforeValidation($this->_request->get($input->getDbField()));
            if ($newValue !== null) {
                $merge[$input->getDbField()] = $newValue;
            }

            $rules[$input->getDbField()] = $input->getValidations($validationType);

            $messages = array_merge($messages, $input->getValidationMessages());

            $labels[$input->getDbField()] = $input->getLabel();
        }

        if ($merge) {
            $this->_request->merge($merge);
        }

        $validator = \Validator::make($this->_request->all(), $rules, $messages, $labels);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $this->postData = $validator->validated();

        return true;
    }

    private function createPostData($id = null)
    {
        $postData = $this->postData;
        $this->postData = [];

        if ($this->formStatus == FormStatus::Store) {
            $this->postData['createdBy'] = Session::has('user') ? Session::get('user')->userId : 0;
            $this->postData['createdAt'] = \date('Y-m-d H:i:s');
        } else {
            if ($id) {
                $this->_editId = $id;
            } elseif (! $this->_editId) {
                $parse = $this->parseEditId();
                if (true !== $parse) {
                    return $parse;
                }
            }

            if (! $this->oldData) {
                $this->oldData = $this->_model->getOne($this->_editId);
            }

            $this->postData['updatedBy'] = Session::has('user') ? Session::get('user')->userId : 0;
            $this->postData['updatedAt'] = \date('Y-m-d H:i:s');
        }

        $this->formatMultiple();

        // I think we should not remove the meta data like dates and updatedby
        // Remove if there is any extra fields that are not needed
        /*foreach ($this->postData as $key => $val) {
            if (isset($this->options->doNotSave) && $this->options->doNotSave) {
                if (\in_array($key, $this->options->doNotSave)) {
                    unset($this->postData[$key]);
                    continue;
                }
            }
            if (isset($this->options->saveOnly) && $this->options->saveOnly) {
                if (! \in_array($key, $this->options->saveOnly)) {
                    unset($this->postData[$key]);
                    continue;
                }
            }
        }*/

        foreach ($this->_bluePrint->getList() as $input) {
            $dbField = $input->getDbField();

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

            if ($input instanceof BluePrint) {
                if (! $input->getModel()) {
                    $this->postData[$input->getKey()] = \json_encode($this->_request[$input->getKey()]);
                }

                continue;
            }

            // If we don't have a postdata for an field like for an optional file field
            $this->postData[$dbField] = $postData[$dbField] ?? null;

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

    private function formatMultiple()
    {
        $data = $this->_request->all();

        $merge = [];
        foreach ($this->_bluePrint->getList() as $input) {
            if (! $input instanceof BluePrint) {
                continue;
            }

            $value = $data[$input->getKey()] ?? null;
            if (\is_array($value)) {
                $keys = array_keys($value);
                if (! $keys) {
                    continue;
                }

                $totalRows = \count($value[$keys[0]]);
                $totalKeys = \count($keys);

                $newData = [];
                for ($i = 0; $i < $totalRows; $i++) {
                    $newRow = [];
                    for ($j = 0; $j < $totalKeys; $j++) {
                        $newRow[$keys[$j]] = $value[$keys[$j]][$i];
                    }

                    $newData[] = $newRow;
                }

                $merge[$input->getKey()] = $newData;
            }
        }

        $this->_request->merge($merge);

        /* Need this for file upload and other callbacks

        $arrayToMerge = [];
        foreach ($this->_bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                $row = [];

                foreach ($input->getList() as $field) {
                    //$response = $input->beforeStore((object)$this->postData);
                    //$row[$field->dbField()] =
                }

                $arrayToMerge[$input->getKey()] = 1;
            }
        }*/
    }

    private function parseEditId()
    {
        $url = $this->_request->getRequestUri();

        $matches = [];
        $t = \preg_match('/'.$this->_resource->route.'\/([^\/]*)\/?/', $url, $matches);
        if (\count($matches) > 1) {
            $this->_editId = $matches[1];

            return true;
        }

        return redirect($this->_url)->with('error', 'Could not fetch "id"! Call update manually.');
    }

    //endregion

    public function destroy($id = false)
    {
        $this->formStatus = FormStatus::Destroy;

        if (! $id) {
            $url = $this->_request->getRequestUri();

            $matches = [];
            $t = \preg_match('/'.$this->_resource->route.'\/([^\/]*)\/?/', $url, $matches);
            if (\count($matches) > 1) {
                $id = $matches[1];
            } else {
                return redirect($this->_url)->with('error', 'Could not fetch "id"! Call update manually.');
            }
        }

        // TODO:
        // validations
        //      permission to delete
        //      can delete this row

        $result = $this->_model->getOne($id);

        if ($result) {
            foreach ($this->_bluePrint->getList() as $field) {
                if ($field instanceof BluePrint) {
                    // TODO:
                } else {
                    $field->beforeDestroy($result);
                }
            }
        } else {
            // TODO: Log result not found on delete id
        }

        $affected = $this->_model->deleteOne($id);

        if ($affected > 0 && $result) {
            foreach ($this->_bluePrint->getList() as $field) {
                if ($field instanceof BluePrint) {
                    // TODO:
                } else {
                    $field->afterDestroy($result);
                }
            }

            // This will only execute if the method called by default not manually from the destroy
            /*if (\method_exists($this->_resource, 'destroy')) {
                $this->_resource->destroy($id);
            }*/
        }

        return redirect($this->_url)->with('success', 'Data deleted successfully!');
    }

    //region GetterSetter

    public function getPostData($id = null)
    {
        $result = $this->createPostData($id);
        if ($result !== true) {
            return $result;
        }

        return $this->postData;
    }

    public function setPostData($data)
    {
        $this->postData = $data;
    }

    public function getModel()
    {
        return $this->_model;
    }

    public function getId()
    {
        return $this->_editId;
    }

    public function getData()
    {
        return $this->resultData;
    }

    public function setCrud(Crud $crud)
    {
        $this->crud = $crud;
    }

    //endregion
}
