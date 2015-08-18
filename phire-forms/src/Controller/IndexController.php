<?php

namespace Phire\Forms\Controller;

use Phire\Forms\Model;
use Phire\Forms\Form;
use Phire\Forms\Table;
use Phire\Controller\AbstractController;

class IndexController extends AbstractController
{

    /**
     * Index action method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('forms/index.phtml');
        $forms = new Model\Form();

        $this->view->title     = 'Forms';
        $this->view->forms = $forms->getAll($this->request->getQuery('sort'), $this->application->isRegistered('phire-fields'));

        $this->send();
    }

    /**
     * Add action method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('forms/add.phtml');
        $this->view->title = 'Forms : Add';

        $fields = $this->application->config()['forms']['Phire\Forms\Form\Form'];

        $this->view->form = new Form\Form($fields);

        if ($this->request->isPost()) {
            $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
                 ->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $form = new Model\Form();
                $form->save($this->view->form->getFields());
                $this->view->id = $form->id;
                $this->redirect(BASE_PATH . APP_URI . '/forms/edit/'. $form->id . '?saved=' . time());
            }
        }

        $this->send();
    }

    /**
     * Edit action method
     *
     * @param  int $id
     * @return void
     */
    public function edit($id)
    {
        $form = new Model\Form();
        $form->getById($id);

        if (!isset($form->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }

        $this->prepareView('forms/edit.phtml');
        $this->view->title         = 'Forms';
        $this->view->form_name = $form->name;

        $fields = $this->application->config()['forms']['Phire\Forms\Form\Form'];
        $fields[1]['name']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

        $this->view->form = new Form\Form($fields);
        $this->view->form->addFilter('htmlentities', [ENT_QUOTES, 'UTF-8'])
             ->setFieldValues($form->toArray());

        if ($this->request->isPost()) {
            $this->view->form->setFieldValues($this->request->getPost());

            if ($this->view->form->isValid()) {
                $this->view->form->clearFilters()
                     ->addFilter('html_entity_decode', [ENT_QUOTES, 'UTF-8'])
                     ->filter();
                $form = new Model\Form();

                $form->update($this->view->form->getFields());
                $this->view->id = $form->id;
                $this->redirect(BASE_PATH . APP_URI . '/forms/edit/'. $form->id . '?saved=' . time());
            }
        }

        $this->send();
    }

    /**
     * Remove action method
     *
     * @return void
     */
    public function remove()
    {
        if ($this->request->isPost()) {
            $form = new Model\Form();
            $form->remove($this->request->getPost());
        }
        $this->redirect(BASE_PATH . APP_URI . '/forms?removed=' . time());
    }

    /**
     * Prepare view
     *
     * @param  string $form
     * @return void
     */
    protected function prepareView($form)
    {
        $this->viewPath = __DIR__ . '/../../view';
        parent::prepareView($form);
    }

}
