<?php

namespace Deep\FormTool\Core;

use Closure;
use Deep\FormTool\Core\InputTypes\Common\InputType;
use Deep\FormTool\Core\InputTypes\Common\ISaveable;
use Deep\FormTool\Core\Interfaces\SimpleRestApiInterface;
use Deep\FormTool\Exceptions\FormToolException;
use Deep\FormTool\Models\MultipleTableModel;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class Form
{
    private $bluePrint;
    private $resource;
    private $model;

    private int $formStatus = 0;

    private $request;

    private $editId;

    private $queryString = '';

    private $resultData = null;
    private $postData = [];
    private $updatePostData = [];
    private $oldData = null;
    private $saveAtInputs = [];
    private $dataAfterSave = [];

    private Crud $crud;
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

    private $eventCallbacks = [];
    private $htmlForm = [
        'action' => null,
        'method' => 'POST',
        'isButtonSubmit' => true,
        'buttonSubmit' => 'Save',
        'isButtonCancel' => true,
        'buttonCancel' => 'Cancel',
        'cancel' => null,
    ];

    public function __construct($resource, ?BluePrint $bluePrint, ?DataModel $model)
    {
        $this->resource = $resource;
        $this->bluePrint = $bluePrint;
        $this->model = $model;

        $this->options = new \stdClass();

        $this->request = request();
        $this->queryString = $this->request->getQueryString();

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

    public function callbackValidation(callable $callbackValidation): Crud
    {
        $this->callbackValidation = $callbackValidation;

        return $this->crud;
    }

    public function unique(array $columns): Crud
    {
        $this->uniqueColumns = [];
        $columns = array_values($columns);
        foreach ($columns as $col) {
            $col = false !== strpos($col, '.') ? trim(explode('.', $col)[1] ?? '') : trim($col);

            if (! $this->bluePrint->getInputTypeByDbField($col)) {
                throw new \InvalidArgumentException(sprintf('Field "%s" not found for unique validation', $col));
            }
        }

        $this->uniqueColumns = $columns;

        return $this->crud;
    }

    public function onEvent(EventType|array $eventTypes, Closure $closure): Crud
    {
        $eventTypes = Arr::wrap($eventTypes);

        foreach ($eventTypes as $eventType) {
            $this->eventCallbacks[] = (object) ['type' => $eventType->value, 'closure' => $closure];
        }

        return $this->crud;
    }

    public function formMethod($method)
    {
        $method = strtoupper(trim($method));
        if (! in_array($method, ['POST', 'GET'])) {
            throw new FormToolException('Form method must be GET or POST');
        }

        $this->htmlForm['method'] = $method;

        return $this->crud;
    }

    public function formAction($action)
    {
        $this->htmlForm['action'] = $action;

        return $this->crud;
    }

    public function formSubmitButton($label, $isVisible = true)
    {
        $this->htmlForm['buttonSubmit'] = trim($label) ?: 'Save';
        $this->htmlForm['isButtonSubmit'] = $isVisible;

        return $this->crud;
    }

    public function formCancelButton($label, $isVisible = true, $cancelUrl = null)
    {
        $this->htmlForm['buttonCancel'] = trim($label) ?: 'Cancel';
        $this->htmlForm['isButtonCancel'] = $isVisible;
        $this->htmlForm['cancel'] = $cancelUrl;

        return $this->crud;
    }

    //endregion

    //region GenerateForm

    public function create()
    {
        $data = new \stdClass();

        $data->fields = new \stdClass();

        $n = 0;
        foreach ($this->bluePrint->getList() as $input) {
            if ($input instanceof BluePrint) {
                $data->fields->{$input->getKey()} = $this->getMultipleTable($input);
            } else {
                // Let's modify value before if needed like for decryption
                // We wil not modify anything on create as default values must be set as it is
                if ($this->formStatus == FormStatus::EDIT) {
                    $input->setValue($input->getValue());
                }

                $data->fields->{$input->getDbField() ?: $n++} = $input->getHTML();
            }
        }

        $data->isEdit = $this->formStatus == FormStatus::EDIT;
        $url = createUrl($this->resource->route, $this->queryString);

        $data = (object) array_merge((array) $data, $this->htmlForm);
        if ($data->action) {
            if (false !== strpos($data->action, 'http')) {
                $data->action = createUrl($data->action);
            }
        } else {
            $data->action = $url;
        }

        if ($this->request->query('redirect')) {
            $data->cancel = urldecode($this->request->query('redirect'));
        } else {
            if ($data->cancel) {
                if (false !== strpos($data->cancel, 'http')) {
                    $data->cancel = createUrl($data->cancel);
                }
            } else {
                $data->cancel = $url;
            }
        }

        if ($data->isEdit) {
            $editId = $this->editId = $this->model->isToken() ?
                $this->resultData->{$this->model->getTokenCol()} :
                ($this->resultData->{$this->model->getPrimaryId()} ?? null);

            if ($editId) {
                $data->action = createUrl($this->resource->route.'/'.$editId, $this->queryString);
            } else {
                $data->action = $url;
            }
        }

        $data->action = str_replace(['{id}', '{query_string}'], [$this->editId, $this->queryString], $data->action);
        $data->cancel = str_replace(['{id}', '{query_string}'], [$this->editId, $this->queryString], $data->cancel);

        return $data;
    }

    private function getMultipleTable($model)
    {
        $key = $model->getFullKey();
        $keyName = \str_replace(['[', ']'], '-', $key);

        // Getting the template at the beginning will make sure that it will not contain any values of the field
        $template = $this->getTableRow($model, $key);

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
        foreach ($model->getGroups() as $groupName => $group) {
            if (is_string($groupName)) {
                $tableData->header[] = (object) ['label' => $groupName, 'isRequired' => false, 'help' => ''];
                $totalCols++;
                continue;
            }

            foreach ($group as $field) {
                if (! $field instanceof BluePrint) {
                    if ($field->getType() != InputType::HIDDEN) {
                        $tableData->header[] = (object) ['label' => $field->getLabel(), 'isRequired' => $field->isRequired(), 'help' => $field->getHelp()];
                        $totalCols++;
                    }
                } else {
                    $tableData->header[] = (object) ['label' => '', 'isRequired' => false, 'help' => ''];
                    $totalCols++;
                }
            }
        }
        $tableData->header[] = (object) ['label' => '', 'isRequired' => false, 'help' => ''];

        // TODO: Need to work for only one file field
        // Check if any session data exists
        $totalDataInSession = 0;
        if (count($model->getInputList()) > 0) {
            $field = $model->getInputList()[0]->getDbField();
            $val = old($key);
            if ($val && \is_array($val)) {
                $totalDataInSession = \count($val);
            }
        }

        // Let's get data for multiple fields if its Edit
        $result = null;
        $totalRowsInEdit = 0;

        // TODO: Need to check if validation failed without $totalDataInSession
        if (! $totalDataInSession && $this->formStatus == FormStatus::EDIT) {
            // $dbModel = $model->getModel();
            if ($model->getModel()) {
                // if ($dbModel instanceof \stdClass) {
                //     $where = [$dbModel->foreignKey => $this->editId];

                //     $query = DB::table($dbModel->table)->where($where);
                //     if ($dbModel->where) {
                //         $closure = $dbModel->where;
                //         $closure($query);
                //     }
                //     if ($dbModel->orderBy) {
                //         $query->orderBy($dbModel->orderBy, 'asc');
                //     } elseif ($model->getOrderByColumn()) {
                //         $query->orderBy($model->getOrderByColumn());
                //     } else {
                //         $query->orderBy($dbModel->id, 'asc');
                //     }

                //     $result = $query->get();
                // } else {
                //     if ($model->getOrderByColumn()) {
                //         $dbModel::$orderBy = $model->getOrderByColumn();
                //     }

                //     $where = [$dbModel::$foreignKey => $this->editId];
                //     $result = $dbModel::getWhere($where);
                // }

                $result = MultipleTableModel::init($model->getModel())->getAll($this->editId);
            } elseif (isset($this->resultData->{$key})) {
                $result = \json_decode($this->resultData->{$key});
            }

            if ($result && is_countable($result)) {
                $totalRowsInEdit = \count($result);

                $i = 0;
                foreach ($result as $row) {
                    foreach ($model->getInputList() as $field) {
                        $field->setIndex($model->getKey(), $i);

                        $field->setValue($row->{$field->getDbField()} ?? null);
                    }

                    $tableData->rows .= $this->getTableRow($model, $key, $i++);
                }
            }
        }

        $appendCount = 0;
        if ($model->getRequired() > 0) {
            // Check if the required items is greater than the items already saved
            if ($result) {
                $appendCount = $model->getRequired() - $totalRowsInEdit;
            } elseif (! $result || $this->formStatus == FormStatus::CREATE) {
                $appendCount = $model->getRequired();
            }
        }

        if ($appendCount < $totalDataInSession) {
            $appendCount = $totalDataInSession;
        }

        for ($i = 0; $i < $appendCount; $i++) {
            // Sending index will get the field value if there any in the session
            $tableData->rows .= $this->getTableRow($model, $key, $i + $totalRowsInEdit);
        }

        $tableData->totalColumns = ++$totalCols;

        Doc::addJs('template["'.$keyName.'"]=`'.$template.'`', $keyName);

        $data['table'] = $tableData;

        return \view('form-tool::form.multiple_table', $data)->render();
    }

    private function getTableRow($model, $key, $index = -1)
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

        foreach ($model->getGroups() as $groupName => $group) {
            $columns = [];
            foreach ($group as $field) {
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
                        $columns[] = $field->getHTMLMultiple($key, $index, $oldValue);
                    }
                }
            }

            if (is_numeric($groupName)) {
                foreach ($columns as $column) {
                    $rowData->columns .= '<td>'.$column.'</td>';
                }
            } else {
                $rowData->columns .= '<td>';
                foreach ($columns as $column) {
                    $rowData->columns .= '<div class="mb-2">'.$column.'</div>';
                }
                $rowData->columns .= '</td>';
            }
        }

        $data['row'] = $rowData;

        return \view('form-tool::form.multiple_table_row', $data)->render();
    }

    //endregion

    public function edit($id = null)
    {
        $this->formStatus = FormStatus::EDIT;

        $this->resultData = null;
        if ($this->crud->isDefaultFormat()) {
            if (! $id) {
                $url = $this->request->getRequestUri();

                $matches = [];
                \preg_match('/'.$this->resource->route.'\/([^\/]*)\/edit/', $url, $matches);
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

        foreach ($this->bluePrint->getInputList() as $input) {
            if (! $input instanceof BluePrint) {
                $input->setValue($this->resultData->{$input->getDbField()} ?? null);
            }
        }
    }

    //region FormAction StoreAndUpdate

    public function store()
    {
        $this->formStatus = FormStatus::STORE;

        $validate = $this->validate();
        if ($validate !== true) {
            return $validate;
        }

        $this->createPostData();

        $insertId = $this->model->add($this->postData);

        if ($insertId) {
            $this->editId = $insertId;

            $this->dataAfterSave = $this->postData;
            $this->dataAfterSave[$this->model->getPrimaryId()] = $insertId;

            $this->afterSave();

            ActionLogger::create($this->bluePrint, $insertId);

            $this->invokeEvent(EventType::CREATE);

            // Let's handle quick add
            $response = $this->handleQuickAdd($insertId);
            if ($response) {
                return $response;
            }

            $heroField = $this->bluePrint->getHeroField();
            $heroValue = '';
            if ($heroField && ($this->postData[$heroField] ?? null)) {
                $heroValue = ' <b>'.$this->postData[$heroField].'</b>';
            }

            if (! $this->request->has('redirect')) {
                $this->request->merge(['redirect' => createUrl($this->resource->route.'/create', $this->queryString)]);
            }

            return $this->response(true, ($this->resource->singularTitle ?? 'Data').$heroValue.' added successfully!');
        }

        return $this->response(false, 'Something went wrong! Data not added successfully!');
    }

    public function update($id = null, ?callable $callbackBeforeUpdate = null)
    {
        $this->formStatus = FormStatus::UPDATE;

        if ($this->crud->isDefaultFormat()) {
            $id = $this->parseEditId($id);
        }

        $validate = $this->validate();
        if ($validate !== true) {
            return $validate;
        }

        if ($this->crud->isDefaultFormat()) {
            if (! $this->oldData) {
                $this->oldData = $this->model->getOne($this->editId);
            }

            if (! $this->oldData) {
                // $message = 'Something went wrong! Data not found, please try again!';
                // if ($this->crud->isWantsArray()) {
                //     return ['status' => false, 'message' => $message];
                // } elseif ($this->isWantsJson()) {
                //     return response()->json(['status' => false, 'message' => $message], 422);
                // }

                // return redirect(createUrl($this->resource->route, $this->queryString))->with('error', $message);

                return $this->response(false, 'Something went wrong! Data not found, please try again!');
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

        $this->createPostData($this->editId);

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

        $this->invokeEvent(EventType::UPDATE);

        $heroField = $this->bluePrint->getHeroField();
        $heroValue = '';
        if ($heroField && ($this->postData[$heroField] ?? null)) {
            $heroValue = ' <b>'.$this->postData[$heroField].'</b>';
        }

        // $message = ($this->resource->singularTitle ?? 'Data').$heroValue.' updated successfully!';
        // if ($this->crud->isWantsArray()) {
        //     return ['status' => true, 'message' => $message];
        // } elseif ($this->isWantsJson()) {
        //     return response()->json(['status' => true, 'message' => $message]);
        // }

        // $redirect = $this->request->query('redirect');
        // if ($redirect) {
        //     return redirect(urldecode($redirect))->with('success', $message);
        // }

        // return redirect(createUrl($this->resource->route, $this->queryString))->with('success', $message);

        return $this->response(true, ($this->resource->singularTitle ?? 'Data').$heroValue.' updated successfully!');
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

        foreach ($this->bluePrint->getInputList() as $input) {
            if ($input instanceof BluePrint) {
                continue;
            }

            if ($this->formStatus == FormStatus::STORE) {
                $input->afterStore($result);
            } else {
                $input->afterUpdate($this->oldData, $result);
            }
        }

        $this->doSaveAt();

        if ($this->crud->isDefaultFormat()) {
            $this->saveMultipleFields();
        }
    }

    private function saveMultipleFields()
    {
        foreach ($this->bluePrint->getInputList() as $input) {
            if (! $input instanceof BluePrint) {
                continue;
            }

            // $model = $input->getModel();

            // $foreignKey = null;
            // if ($model) {
            //     if ($model instanceof \stdClass) {
            //         $foreignKey = $model->foreignKey;
            //     } else {
            //         if (! isset($model::$foreignKey)) {
            //             throw new \InvalidArgumentException('$foreignKey property not defined at '.$model);
            //         }

            //         $foreignKey = $model::$foreignKey;
            //     }
            // }

            $model = null;
            if ($input->getModel()) {
                $model = MultipleTableModel::init($input->getModel());
            }

            $postData = $this->request->post($input->getKey());

            // Some field don't send data into the post like file
            // And it's a buggy if we only have only file field in multiple
            // So let's create an array of null if have any data in our file field
            // count() help to determine on edit with some data in post and some new upload
            if ($postData == null || count($input->getInputList()) == 1) {
                foreach ($input->getInputList() as $field) {
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
                    foreach ($input->getInputList() as $field) {
                        $field->setIndex($input->getKey(), $index);

                        $dbField = $field->getDbField();

                        // If we don't have a post data for an field like for an optional file field
                        $dataRow[$dbField] = $row[$dbField] ?? null;

                        $field->setValue($dataRow[$dbField]);

                        $response = null;
                        if ($this->formStatus == FormStatus::STORE) {
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

                        if ($this->formStatus == FormStatus::STORE) {
                            $field->afterStore((object) $dataRow);
                        } else {
                            $field->afterUpdate((object) $row, (object) $dataRow);
                        }
                    }

                    if ($model) {
                        $dataRow[$model->getForeignCol()] = $this->editId;
                    }

                    $data[] = $dataRow;
                }
            }

            if ($model) {
                // $where = [$foreignKey => $this->editId];
                // if ($model instanceof \stdClass) {
                //     $query = DB::table($model->table);
                //     if ($model->where) {
                //         $closure = $model->where;
                //         $closure($query);
                //     }
                //     $query->where($where)->delete();
                //     if (\count($data)) {
                //         DB::table($model->table)->insert($data);
                //     }
                // } else {
                //     $model::deleteWhere($where);
                //     if (\count($data)) {
                //         $model::addMany($data);
                //     }
                // }

                $model->add($this->editId, $data);
            } else {
                $this->model->updateOne($this->editId, [$input->getKey() => json_encode($data)]);
            }
        }
    }

    private function doSaveAt()
    {
        $postData = $this->request->input();

        foreach ($this->saveAtInputs as $input) {
            $saveAt = $input->getSaveAt();
            $values = $postData[$input->getDbField()] ?? null;

            // This help to store multiple values from a API request
            if (is_string($values)) {
                $values = json_decode($values, true);
            }

            $data = [];
            $this->postData[$input->getDbField()] = [];

            if ($values && is_array($values)) {
                // Filter the values, as sometime it gives us falsy values like [null]
                $values = array_filter($values);

                foreach ($values as $val) {
                    $data[] = [
                        $input->getDbField() => $val,
                        $saveAt->refId => $this->editId,
                    ];

                    $this->postData[$input->getDbField()][] = $val;
                }
            }
            $input->setValue($this->postData[$input->getDbField()]);

            DB::table($saveAt->table)->where($saveAt->refId, $this->editId)->delete();
            if (\count($data)) {
                DB::table($saveAt->table)->insert($data);
            }
        }
    }

    private function handleQuickAdd($insertId)
    {
        $optionData = $this->request->input('_option');
        if (! $optionData) {
            return false;
        }

        [$route, $fieldName] = explode('.', $optionData);
        if (! $route || ! $fieldName) {
            return false;
        }

        $result = DB::table('cruds')->where('route', $route)->first();
        if (! $result) {
            return false;
        }

        $parentClass = (new $result->classPath());
        if (! $parentClass) {
            return false;
        }

        $parentClass->setup();
        $quickParentCrud = $parentClass->getCrud();
        if (! $quickParentCrud) {
            return false;
        }

        $field = $quickParentCrud->getBluePrint()->getInputTypeByDbField($fieldName);
        if (! $field) {
            return false;
        }

        $options = $field->getOptions($insertId);

        return response()->json(['status' => true, 'data' => [
            'options' => $options,
        ]]);
    }

    public function validate()
    {
        $validationType = $this->formStatus == FormStatus::UPDATE ? 'update' : 'store';

        $rules = $messages = $labels = $merge = [];
        foreach ($this->bluePrint->getInputList() as $input) {
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

        $validator = Validator::make($this->request->all(), $rules, $messages, $labels);

        if ($validator->fails()) {
            return $this->response(false, null, $validator);
        }

        if ($this->uniqueColumns) {
            $where = [];
            $combination = [];

            $postData = (object) $this->request->post();
            foreach ($this->uniqueColumns as $column) {
                $alias = $this->model->getAlias();
                if (false !== strpos($column, '.')) {
                    [$alias, $column] = explode('.', $column);
                }

                $input = $this->bluePrint->getInputTypeByDbField($column);

                // Before store is called so that value like date can be converted to db date format
                $input->setValue($postData->{$column} ?? null);
                $value = $input->beforeStore($postData) ?? $input->getDefaultValue();

                $alias = $alias ? $alias.'.' : '';
                $where[] = [$alias.$column => $value];
                $combination[] = $input->getNiceValue($value) ?: $input->getDefaultValue();
            }

            $alias = $this->model->getAlias() ? $this->model->getAlias().'.' : '';
            if ($this->formStatus == FormStatus::UPDATE) {
                $where[] = function ($query) use ($alias) {
                    $query->where($alias.$this->model->getPrimaryId(), '!=', $this->editId);
                };
            }

            $count = $this->model->countWhere($where);
            if ($count) {
                // $message = \sprintf(
                //     'The combination of "%s" is already exist!',
                //     \implode(', ', array_values($combination))
                // );

                // if ($this->crud->isWantsArray()) {
                //     return ['status' => false, 'message' => $message];
                // } elseif ($this->isWantsJson()) {
                //     return response()->json(['status' => false, 'message' => $message], 422);
                // }

                // return back()->with('error', $message)->withInput();

                return $this->response(false, \sprintf('The combination of "%s" is already exist!', \implode(', ', array_values($combination))));
            }
        }

        if ($this->callbackValidation) {
            $callbackValidation = $this->callbackValidation;

            /**
             * $response can be string, \Illuminate\Http\RedirectResponse, \Illuminate\Http\JsonResponse.
             *
             * @var $response string|\Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
             */
            $response = $callbackValidation($this->request, $validationType);
            if ($response !== true) {
                // if (\is_string($response)) {
                //     if ($this->crud->isWantsArray()) {
                //         return ['status' => false, 'message' => $response];
                //     } elseif ($this->isWantsJson()) {
                //         return response()->json(['status' => false, 'message' => $response], 422);
                //     }

                //     return back()->with('error', $response)->withInput();
                // } elseif ($response instanceof \Illuminate\Http\RedirectResponse) {
                //     return $response->send();
                // } elseif ($response instanceof \Illuminate\Http\JsonResponse) {
                //     return $response->send();
                // } else {
                //     $message = 'Validation failed: NO_CUSTOM_MESSAGE_RETURNED_FROM_CALLBACK_VALIDATION';
                //     if ($this->crud->isWantsArray()) {
                //         return ['status' => false, 'message' => $message];
                //     } elseif ($this->isWantsJson()) {
                //         return response()->json(['status' => false, 'message' => $message], 422);
                //     }

                //     return back()->with('error', $message)->withInput();
                // }

                if ($response) {
                    return $this->response(false, $response);
                } else {
                    return $this->response(false, 'Validation failed: NO_CUSTOM_MESSAGE_RETURNED_FROM_CALLBACK_VALIDATION');
                }
            }
        }

        $this->postData = $validator->validated();

        return true;
    }

    public function invokeEvent(EventType $eventType, $id = null, $data = null)
    {
        foreach ($this->eventCallbacks as $callback) {
            if ($callback->type == $eventType->value || $callback->type == EventType::ALL->value) {
                $closure = $callback->closure;
                $closure($id ?? $this->editId, (object) ($data ?? $this->getData()));
            }
        }
    }

    private function createPostData($id = null): void
    {
        if (! $id) {
            $id = $this->editId;
        }

        $postData = $this->postData;
        $this->postData = [];

        if ($this->formStatus == FormStatus::UPDATE) {
            $id = $this->parseEditId($id);

            if (! $this->oldData) {
                $this->oldData = $this->model->getOne($this->editId);
            }
        }

        // I think we should not remove the meta data like dates and updatedBy
        // Remove if there is any extra fields that are not needed

        foreach ($this->bluePrint->getInputList() as $input) {
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
                    $bluePrint = $input;
                    $postKey = $bluePrint->getKey();
                    $multiplePostData = $this->request[$postKey] ?? null;

                    $newMultiplePostData = [];
                    if ($multiplePostData && is_array($multiplePostData)) {
                        foreach ($multiplePostData as $key => $value) {
                            foreach ($bluePrint->getInputList() as $input) {
                                $newMultiplePostData[$key][$input->getDbField()] = $value[$input->getDbField()];

                                $newMultiplePostData[$key][$input->getDbField()] = $this->getPostValue($input, $newMultiplePostData[$key][$input->getDbField()], $value, (object) []);
                            }
                        }
                    }

                    // If we have a select multiple field, we need to prevent it from double encoding when we have KeyValue CRUD format
                    $this->postData[$postKey] = \json_encode($newMultiplePostData);
                }

                continue;
            }

            if ($input instanceof ISaveable && $input->isSaveAt()) {
                $this->saveAtInputs[$dbField] = $input;

                continue;
            }

            // If we don't have a post data for an field like for an optional file field
            // Reassigning the postData is important
            $this->postData[$dbField] = $postData[$dbField] ?? null;

            $this->postData[$dbField] = $this->getPostValue($input, $this->postData[$dbField], $this->postData, $this->oldData);

            // $input->setValue($this->postData[$dbField]);

            // $response = null;
            // if ($this->formStatus == FormStatus::STORE) {
            //     $response = $input->beforeStore((object) $this->postData);
            // } else {
            //     $response = $input->beforeUpdate($this->oldData, (object) $this->postData);
            // }
            //
            // if ($response !== null) {
            //     $this->postData[$dbField] = $response;
            // }
            //
            // if ($this->postData[$dbField] === null && $input->getDefaultValue() !== null) {
            //     $this->postData[$dbField] = $input->getDefaultValue();
            //     $input->setValue($this->postData[$dbField]);
            // }
        }
    }

    private function getPostValue($input, $postValue, $postData, $oldData)
    {
        $input->setValue($postValue);

        $response = null;
        if ($this->formStatus == FormStatus::STORE) {
            $response = $input->beforeStore((object) $postData);
        } else {
            $response = $input->beforeUpdate($oldData, (object) $postData);
        }

        if ($response !== null) {
            $postValue = $response;
        }

        if ($postValue === null && $input->getDefaultValue() !== null) {
            $postValue = $input->getDefaultValue();
            $input->setValue($postValue);
        }

        return $postValue;
    }

    private function parseEditId($id)
    {
        if ($id) {
            $this->editId = $id;

            return $id;
        }

        $url = $this->request->getRequestUri();

        $matches = [];
        \preg_match('/'.$this->resource->route.'\/([^\/\?$]*)/', $url, $matches);
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

        $this->formStatus = FormStatus::DELETE;

        $id = $this->parseEditId($id);

        $result = $this->model->getOne($id);
        if (! $result) {
            // $message = 'Something went wrong! Data not found, please try again!';
            // if ($this->crud->isWantsArray()) {
            //     return ['status' => false, 'message' => $message];
            // } elseif ($this->isWantsJson()) {
            //     return response()->json(['status' => false, 'message' => $message], 422);
            // }

            // return redirect(createUrl($this->resource->route, $this->queryString))->with('error', $message);

            return $this->response(false, 'Something went wrong! Data not found, please try again!');
        }

        $pId = $id;
        if ($this->model->isToken()) {
            $pId = $result->{$this->model->getPrimaryId()} ?? null;
        }

        $response = $this->checkForeignKeyRestriction($pId, $result);
        if ($response !== true) {
            return $response;
        }

        $this->model->updateDelete($pId);

        ActionLogger::delete($this->bluePrint, $pId, $result);

        $this->invokeEvent(EventType::DELETE, $pId, $result);

        $heroField = $this->bluePrint->getHeroField();
        $heroValue = '';
        if ($heroField && ($result->{$heroField} ?? null)) {
            $heroValue = $result->{$heroField};
        }

        // $message = ($this->resource->singularTitle ?? 'Data').' <b>'.$heroValue.'</b> deleted successfully!';
        // if ($this->crud->isWantsArray()) {
        //     return ['status' => true, 'message' => $message, 'data' => ['heroValue' => $heroValue]];
        // } elseif ($this->isWantsJson()) {
        //     return response()->json(['status' => true, 'message' => $message, 'data' => ['heroValue' => $heroValue]]);
        // }

        // return redirect(createUrl($this->resource->route, $this->queryString))->with('success', $message);

        return $this->response(true, ($this->resource->singularTitle ?? 'Data').' <b>'.$heroValue.'</b> deleted successfully!', null, ['heroValue' => $heroValue]);
    }

    public function destroy($id = null)
    {
        $this->formStatus = FormStatus::DESTROY;

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
            // $message = 'Something went wrong! Data not found, please try again!';
            // if ($this->crud->isWantsArray()) {
            //     return ['status' => false, 'message' => $message];
            // } elseif ($this->isWantsJson()) {
            //     return response()->json(['status' => false, 'message' => $message], 422);
            // }

            // return redirect(createUrl($this->resource->route, $this->queryString))->with('error', $message);

            return $this->response(false, 'Something went wrong! Data not found, please try again!');
        }

        $pId = $id;
        if ($this->model->isToken()) {
            $pId = $result->{$this->model->getPrimaryId()} ?? null;
        }

        $response = $this->checkForeignKeyRestriction($pId, $result);
        if ($response !== true) {
            return $response;
        }

        $heroField = $this->bluePrint->getHeroField();
        $heroValue = '';
        if ($heroField && ($result->{$heroField} ?? null)) {
            $heroValue = ' <b>'.$result->{$heroField}.'</b>';
        }

        if ($this->crud->isSoftDelete()) {
            if (! \property_exists($result, $deletedAt)) {
                throw new FormToolException('Column "deletedAt" not found!');
            }

            if ($result->{$deletedAt} === null) {
                // $message = 'Soft delete is enabled for this module. You need to mark as delete first
                //         then only you can delete it permanently!';
                // if ($this->crud->isWantsArray()) {
                //     return ['status' => true, 'message' => $message, 'data' => ['heroValue' => $heroValue]];
                // } elseif ($this->isWantsJson()) {
                //     return response()->json(['status' => true, 'message' => $message, 'data' => ['heroValue' => $heroValue]]);
                // }

                // return redirect(createUrl($this->resource->route, $this->queryString))->with('error', $message);

                return $this->response(
                    false,
                    'Soft delete is enabled for this module. You need to mark as delete first then only you can delete it permanently!',
                    null,
                    ['heroValue' => $heroValue]
                );
            }
        }

        if ($this->doDestroy($id, $result)) {
            // $message = ($this->resource->singularTitle ?? 'Data').$heroValue.' permanently deleted successfully!';
            // if ($this->crud->isWantsArray()) {
            //     return ['status' => true, 'message' => $message, 'data' => ['heroValue' => $heroValue]];
            // } elseif ($this->isWantsJson()) {
            //     return response()->json(['status' => true, 'message' => $message, 'data' => ['heroValue' => $heroValue]]);
            // }

            // return redirect(createUrl($this->resource->route, $this->queryString))->with('success', $message);

            return $this->response(true, ($this->resource->singularTitle ?? 'Data').$heroValue.' permanently deleted successfully!', null, ['heroValue' => $heroValue]);
        }

        // return redirect(createUrl($this->resource->route, $this->queryString))->with(
        //     'error',
        //     'Something went wrong! Data not deleted fully, please contact Support Administrator.'
        // );

        return $this->response(false, 'Something went wrong! Data not deleted fully, please contact Support Administrator.');
    }

    private function doDestroy($id, $result): bool
    {
        $pId = $id;
        if ($this->model->isToken()) {
            $pId = $result->{$this->model->getPrimaryId()} ?? null;
        }

        foreach ($this->bluePrint->getInputList() as $field) {
            if ($field instanceof BluePrint) {
                // TODO: Still now not needed
            } else {
                $field->beforeDestroy($result);
            }
        }

        $affected = $this->model->deleteOne($id);

        if ($affected > 0) {
            foreach ($this->bluePrint->getInputList() as $input) {
                if ($input instanceof BluePrint) {
                    // Let's delete the file and image of sub tables, and data
                    $childResult = [];
                    if ($input->getModel()) {
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

                        // $where = [$foreignKey => $pId];
                        // $childResult = DB::table($model->table)->where($where)->orderBy($model->id, 'asc')->get();

                        // if ($model instanceof \stdClass) {
                        //     DB::table($model->table)->where($where)->delete();
                        // } else {
                        //     $model::deleteWhere($where);
                        // }

                        $model = MultipleTableModel::init($input->getModel());
                        $childResult = $model->getAll($pId);

                        $model->destroy($pId);
                    } else {
                        $childResult = \json_decode($result->{$input->getKey()});
                    }

                    if (is_iterable($childResult)) {
                        foreach ($childResult as $row) {
                            foreach ($input->getInputList() as $childInput) {
                                $childInput->afterDestroy($row);
                            }
                        }
                    }
                } else {
                    $input->afterDestroy($result);
                }
            }

            ActionLogger::destroy($this->bluePrint, $pId, $result);

            $this->invokeEvent(EventType::DESTROY, $pId, $result);

            return true;
        }

        return false;
    }

    private function checkForeignKeyRestriction($id, $dataToDelete)
    {
        if (! \config('form-tool.isPreventForeignKeyDelete', true)) {
            return true;
        }

        $dataCount = [];
        $totalCount = 0;

        $totalReferencesToFetch = 100;

        $result = DB::table('cruds')->get();
        if ($result->count()) {
            foreach ($result as $row) {
                $data = \json_decode($row->data);
                if (! isset($data->foreignKey)) {
                    continue;
                }

                foreach ($data->foreignKey as $option) {
                    if ($option->table == $this->model->getTableName()) {
                        $query = DB::table($data->main->table)->where($option->column, $id);
                        if (isset($option->where)) {
                            $query->where((array) $option->where);
                        }

                        // If we are checking the same table then ignore the id we want to delete
                        if ($data->main->table == $option->table) {
                            $query->where($option->column, '!=', $id);
                        }
                        $resultData = $query->limit($totalReferencesToFetch)->get();

                        $count = $resultData->count();
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
                                break 2;
                            }
                        }
                    }
                }

                // foreignModules will be checked only for the same route
                if ($row->route != $this->resource->route) {
                    continue;
                }

                foreach ($data->foreignModules as $option) {
                    $query = DB::table($option->table)->where($option->column, $id);

                    // If we are checking the same table then ignore the id we want to delete
                    if ($option->table == $this->model->getTableName()) {
                        $query->where($option->column, '!=', $id);
                    }
                    $resultData = $query->limit($totalReferencesToFetch)->get();

                    $count = $resultData->count();
                    if ($count > 0) {
                        $dataCount[] = [
                            'count' => $count,
                            'title' => $option->module,
                            'route' => $option->route,
                            'label' => $option->label,
                            'id' => $option->primaryKey,
                            'result' => $resultData,
                        ];
                        $totalCount += $count;

                        // Let's break, we will not fetch more than $totalReferencesToFetch items
                        if ($totalCount >= $totalReferencesToFetch) {
                            break 2;
                        }
                    }
                }
            }
        }

        if ($dataCount) {
            $message = $this->createMessage($id, $dataToDelete, $dataCount, $totalCount, $totalReferencesToFetch);
            // if ($this->crud->isWantsArray()) {
            //     return ['status' => false, 'message' => $message];
            // } elseif ($this->isWantsJson()) {
            //     return response()->json(['status' => false, 'message' => $message], 422);
            // }

            // return redirect(createUrl($this->resource->route, $this->queryString))->with('error', $message);

            return $this->response(false, $message);
        }

        return true;
    }

    private function createMessage($displayId, $dataToDelete, $dataCount, $totalCount, $totalReferencesToFetch)
    {
        $totalReferencesToDisplay = 10;

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

        $iconExternalLink = config('form-tool.icons.externalLink', 'fa fa-external-link');

        $msg .= '<br /><ul>';
        $i = 0;
        foreach ($dataCount as $result) {
            if ($result['label']) {
                $msg .= \sprintf(
                    '<li>%s data of <b>%s</b> in field <b>%s</b></li>',
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

            $url = $result['route'] ? createUrl($result['route']) : null;
            $hasEditPermission = $result['route'] ? Guard::hasEdit($result['route']) : false;
            $hasDestroyPermission = $result['route'] ? Guard::hasDestroy($result['route']) : false;

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
                                    <i class="'.$iconExternalLink.'"></i></a></li>',
                                $url,
                                $id,
                                $id
                            );
                        }
                    } elseif ($hasEditPermission) {
                        $newMsg = \sprintf(
                            '<li>ID: <a href="%s?id=%s" target="_blank">%s &nbsp <i class="'.$iconExternalLink.'">
                                </i></a></li>',
                            $url,
                            $id,
                            $id
                        );
                    }

                    $msg .= $newMsg;
                } elseif (app()->isLocal()) {
                    if (! property_exists($row, $result['id'])) {
                        $msg .= "<li>ID: <i>{$result['id']} column not found</i></li>";
                    } else {
                        $msg .= "<li>ID: <i>{$result['id']} is blank</i></li>";
                    }
                }

                $i++;
                if ($i >= $totalReferencesToDisplay) {
                    break;
                }
            }
            $msg .= '</ul>';

            if ($i >= $totalReferencesToDisplay && $totalCount > $totalReferencesToDisplay) {
                if ($totalCount == $totalReferencesToFetch) {
                    $msg .= '<li><i>More than '.$totalReferencesToFetch.'+ items...</i></li>';
                } else {
                    $msg .= '<li><i>+'.($totalCount - $totalReferencesToDisplay).' more item(s)...</i></li>';
                }
                break;
            }
        }
        $msg .= '</ul>';

        return $msg;
    }

    private function response($status, $message = null, $validator = null, $data = null)
    {
        if ($validator) {
            if ($this->resource instanceof SimpleRestApiInterface) {
                return $validator;
            }

            $response = [
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->getMessageBag()->toArray(),
                'data' => $data,
            ];

            if ($this->crud->isWantsArray()) {
                return $response;
            } elseif ($this->isWantsJson()) {
                return response()->json($response, 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        if ($message instanceof \Illuminate\Http\RedirectResponse) {
            return $message->send();
        }
        if ($message instanceof \Illuminate\Http\JsonResponse) {
            return $message->send();
        }

        if ($this->resource instanceof SimpleRestApiInterface || $this->crud->isWantsArray()) {
            return ['status' => $status, 'message' => $message, 'data' => $data];
        } elseif ($this->isWantsJson()) {
            if ($status === false) {
                return response()->json(['status' => $status, 'message' => $message, 'data' => $data], 422);
            }

            return response()->json(['status' => $status, 'message' => $message, 'data' => $data]);
        }

        if ($status === true) {
            $redirect = $this->request->input('redirect');
            if ($redirect) {
                return redirect(urldecode($redirect))->with('success', $message);
            }

            return redirect(createUrl($this->resource->route, $this->queryString))->with('success', $message);
        }

        return back()->with('error', $message)->withInput();
    }

    //endregion

    //region GetterSetter

    public function isUpdate()
    {
        return $this->formStatus == FormStatus::UPDATE;
    }

    public function getPostData()
    {
        if ($this->postData) {
            return $this->postData;
        }

        return $this->request->post();
    }

    public function getProcessedData($id = null)
    {
        $this->createPostData($id);

        return $this->postData;
    }

    public function updatePostData($data)
    {
        $this->updatePostData = $data;
    }

    public function getData()
    {
        if ($this->dataAfterSave) {
            return $this->dataAfterSave;
        }

        return $this->getPostData();
    }

    public function getModel(): ?DataModel
    {
        return $this->model;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function getId()
    {
        if (! $this->editId && $this->formStatus != FormStatus::CREATE && $this->formStatus != FormStatus::STORE) {
            $this->parseEditId(null);
        }

        return $this->editId;
    }

    public function getEditData()
    {
        return $this->resultData;
    }

    public function getRoute()
    {
        return $this->resource->route;
    }

    public function getFullUrl()
    {
        return createUrl($this->resource->route, $this->queryString);
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

    public function isWantsJson()
    {
        return $this->crud->isWantsJson() || $this->request->ajax() || $this->request->wantsJson();
    }

    public function setValues(?array $values): void
    {
        if (is_null($values)) {
            return;
        }

        foreach ($values as $column => $value) {
            $input = $this->bluePrint->getInputTypeByDbField($column);
            if ($input) {
                $input->setValue($value);
            }
        }
    }

    public function setFormStatus(int $status)
    {
        $this->formStatus = $status;

        return $this;
    }

    public function setFormStatusStore()
    {
        $this->formStatus = FormStatus::STORE;

        return $this;
    }

    public function setFormStatusUpdate($id = null)
    {
        if ($id) {
            $this->editId = $id;
        }

        $this->formStatus = FormStatus::UPDATE;

        return $this;
    }

    public function setFormStatusSoftDelete($id = null)
    {
        if ($id) {
            $this->editId = $id;
        }

        $this->formStatus = FormStatus::DELETE;

        return $this;
    }

    public function setFormStatusDelete($id = null)
    {
        if ($id) {
            $this->editId = $id;
        }

        $this->formStatus = FormStatus::DESTROY;

        return $this;
    }

    public function setFormStatusDestroy($id = null)
    {
        return $this->setFormStatusDelete($id);
    }

    public function directStore(): bool
    {
        $this->formStatus = FormStatus::STORE;

        $this->createPostData();

        $insertId = $this->model->add($this->postData);

        if ($insertId) {
            $this->editId = $insertId;

            $this->dataAfterSave = $this->postData;
            $this->dataAfterSave[$this->model->getPrimaryId()] = $insertId;

            $this->afterSave();

            ActionLogger::create($this->bluePrint, $insertId);

            $this->invokeEvent(EventType::CREATE);

            return true;
        }

        return false;
    }

    public function directUpdate($id, $data = null)
    {
        $this->formStatus = FormStatus::UPDATE;

        $this->editId = $this->parseEditId($id);

        $this->oldData = $data;
        if (! $this->oldData) {
            $this->oldData = $this->model->getOne($this->editId);
        }

        $this->createPostData($this->editId);

        $this->model->updateOne($this->editId, $this->postData, false);

        $this->afterSave();

        ActionLogger::update($this->bluePrint, $this->editId, $this->oldData, $this->postData);

        $this->invokeEvent(EventType::UPDATE);

        return true;
    }

    public function directDestroy($id, $data = null)
    {
        $this->formStatus = FormStatus::DESTROY;

        $id = $this->parseEditId($id);

        if (! $data) {
            // We can't use getOne, as we need to fetch deleted item
            $idCol = $this->model->isToken() ? $this->model->getTokenCol() : $this->model->getPrimaryId();
            $data = $this->model->getWhereOne([$idCol => $id]);
            if (! $data) {
                return false;
            }
        }

        return $this->doDestroy($id, $data);
    }

    //endregion
}
