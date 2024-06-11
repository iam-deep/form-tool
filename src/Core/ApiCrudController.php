<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\CrudState;
use Illuminate\Http\Request;

trait ApiCrudController
{
    protected $crud = null;

    protected $dataKey = 'data';

    protected function setup()
    {
        $this->dataKey = $this->route;

        // Setup your BluePrint here in the child class
    }

    protected function createList()
    {
        // You may setup the list columns here it have been called in index and search
    }

    public function index(Request $request)
    {
        $this->setup();
        $this->createList();

        $data[$this->dataKey] = $this->crud->index();

        return $this->success($data);
    }

    public function store(Request $request)
    {
        $this->setup();

        $response = $this->crud->store();
        if (isSuccess($response)) {
            $data[$this->dataKey] = $this->crud->getModel()->getAll();

            return $this->success($data);
        }

        return $response;
    }

    public function update(Request $request, $id = null)
    {
        $this->setup();

        $response = $this->crud->update($id);
        if (isSuccess($response)) {
            $data[$this->dataKey] = $this->crud->getModel()->getAll();

            return $this->success($data);
        }

        return $response;
    }

    public function destroy(Request $request, $id = null)
    {
        $this->setup();

        $response = $this->crud->delete($id);
        if (isSuccess($response)) {
            $data[$this->dataKey] = $this->crud->getModel()->getAll();

            return $this->success($data);
        }

        return $response;
    }

    public function search(Request $request)
    {
        $this->setup();
        $this->createList();

        $data[$this->dataKey] = $this->crud->search();

        return $this->success($data);
    }

    public function getOptions(Request $request)
    {
        $state = CrudState::tryFrom($request->post('state'));
        if ($state) {
            Doc::setState($state);
        }

        $this->setup();

        $data['data'] = $this->crud->getOptionsByParentId();

        return $this->success($data);
    }
}
