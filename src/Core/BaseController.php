<?php

namespace Deep\FormTool\Core;

use Deep\FormTool\Core\Doc;
use Deep\FormTool\Core\InputTypes\Common\CrudState;
use Illuminate\Http\Request;

trait BaseController
{
    protected $crud = null;

    public function setup()
    {
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

        $data['title'] = $this->title;

        $data['page'] = $this->crud->index();

        return $this->render('form-tool::list.index', $data);
    }

    public function bulkAction(Request $request)
    {
        $this->setup();

        return $this->crud->bulkAction();
    }

    public function create(Request $request)
    {
        $this->setup();

        $data['title'] = 'Add '.$this->singularTitle;

        $response = $this->crud->create();
        if ($request->query('quickAdd')) {
            return $response;
        }

        $data['page'] = $response;

        return $this->render('form-tool::form.index', $data);
    }

    public function store(Request $request)
    {
        $this->setup();

        return $this->crud->store();
    }

    public function show(Request $request, $id = null)
    {
        $this->setup();
    }

    public function edit(Request $request, $id = null)
    {
        $this->setup();

        $data['title'] = 'Edit '.$this->singularTitle;

        $data['page'] = $this->crud->edit($id);

        return $this->render('form-tool::form.index', $data);
    }

    public function update(Request $request, $id = null)
    {
        $this->setup();

        return $this->crud->update($id);
    }

    public function destroy(Request $request, $id = null)
    {
        $this->setup();

        return $this->crud->delete($id);
    }

    public function search(Request $request)
    {
        $this->setup();
        $this->createList();

        return $this->crud->search();
    }

    public function getOptions(Request $request)
    {
        $state = CrudState::tryFrom($request->post('state'));
        if ($state) {
            Doc::setState($state);
        }

        $this->setup();

        return $this->crud->getOptionsByParentId();
    }

    public function getCrud()
    {
        return $this->crud;
    }
}
