<?php
/**
 * Phire Forms Module
 *
 * @link       https://github.com/phirecms/phire-forms
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 */

/**
 * @namespace
 */
namespace Phire\Forms\Controller;

use Phire\Forms\Model;
use Phire\Forms\Form;
use Phire\Forms\Table;
use Phire\Controller\AbstractController;
use Pop\Data\Data;
use Pop\Paginator\Paginator;

/**
 * Forms Index Controller class
 *
 * @category   Phire\Forms
 * @package    Phire\Forms
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2009-2016 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    http://www.phirecms.org/license     New BSD License
 * @version    1.0.0
 */
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

        if ($forms->hasPages($this->config->pagination)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($forms->getCount(), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $this->view->title = 'Forms';
        $this->view->pages = $pages;
        $this->view->forms = $forms->getAll(
            $limit, $this->request->getQuery('page'), $this->request->getQuery('sort'), $this->application->modules()
        );

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

        $fields = $this->application->config()['forms']['Phire\Forms\Form\FormObject'];

        $this->view->form = new \Pop\Form\Form($fields);
        $this->view->form->setAttribute('id', 'form-form');
        $this->view->form->setIndent('    ');

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
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/forms/edit/'. $form->id);
            }
        }

        $this->send();
    }

    /**
     * Manage action method
     *
     * @param  int $id
     * @return void
     */
    public function manage($id)
    {
        $form = new Model\Form();
        $form->getById($id);

        if (!isset($form->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }

        $this->prepareView('forms/manage.phtml');
        $this->view->title         = 'Forms : Manage Fields';
        $this->view->form_id   = $form->id;
        $this->view->form_name = $form->name;
        $this->view->fields    = $form->getFields($this->application->modules());

        if ($this->request->isPost()) {
            $form->saveFields($this->request->getPost(), $this->application->modules());
            $this->sess->setRequestValue('saved', true);
            $this->redirect(BASE_PATH . APP_URI . '/forms/manage/'. $form->id);
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

        $fields = $this->application->config()['forms']['Phire\Forms\Form\FormObject'];
        $fields[1]['name']['attributes']['onkeyup'] = 'phire.changeTitle(this.value);';

        $this->view->form = new \Pop\Form\Form($fields);
        $this->view->form->setAttribute('id', 'form-form');
        $this->view->form->setIndent('    ');
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
                $this->sess->setRequestValue('saved', true);
                $this->redirect(BASE_PATH . APP_URI . '/forms/edit/'. $form->id);
            }
        }

        $this->send();
    }

    /**
     * Copy action method
     *
     * @param  int $id
     * @return void
     */
    public function copy($id)
    {
        $form = new Model\Form();
        $form->getById($id);

        if (!isset($form->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }

        $form->copy($id, $this->application->modules());
        $this->sess->setRequestValue('saved', true);
        $this->redirect(BASE_PATH . APP_URI . '/forms');
    }

    /**
     * Submissions action method
     *
     * @param  int $id
     * @return void
     */
    public function submissions($id)
    {
        $form = new Model\Form();
        $form->getById($id);

        if (!isset($form->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }

        $submission = new Model\FormSubmission();

        if ($submission->hasPages($id, $this->config->pagination)) {
            $limit = $this->config->pagination;
            $pages = new Paginator($submission->getCount($id), $limit);
            $pages->useInput(true);
        } else {
            $limit = null;
            $pages = null;
        }

        $submissions = $submission->getAll(
            $id, $limit, $this->request->getQuery('page'), $this->request->getQuery('sort'), $this->application->modules()
        );

        $this->prepareView('forms/submissions.phtml');

        $this->view->title          = 'Forms : ' . $form->name . ' : Submissions';
        $this->view->id             = $id;
        $this->view->pages          = $pages;
        $this->view->fieldListLimit = $this->application->module('phire-forms')['field_list_limit'];
        $this->view->fields         = $submissions['fields'];
        $this->view->submissions    = $submissions['rows'];

        $this->send();
    }

    /**
     * View submissions action method
     *
     * @param  int $id
     * @return void
     */
    public function viewSubmissions($id)
    {
        $submission = new Model\FormSubmission();
        $submission->getById($id);

        if (!isset($submission->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }

        $form = new Model\Form();
        $form->getById($submission->form_id);

        $this->prepareView('forms/view.phtml');

        $submissionValues = $submission->getValues($this->application->modules());

        $this->view->title       = 'Forms : ' . $form->name . ' : Submissions : ' . $submission->id;
        $this->view->id          = $submission->id;
        $this->view->formId      = $form->id;
        $this->view->timestamp   = $submission->timestamp;
        $this->view->ip          = $submission->ip_address;
        $this->view->fieldTypes  = $submissionValues['fields'];
        $this->view->fieldValues = $submissionValues['values'];

        $this->send();

    }

    /**
     * Export action method
     *
     * @param  int $id
     * @return void
     */
    public function export($id)
    {
        $form = new Model\Form();
        $form->getById($id);

        if (!isset($form->id)) {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }

        $submission  = new Model\FormSubmission();
        $submissions = $submission->getAll(
            $id, null, null, $this->request->getQuery('sort'), $this->application->modules()
        );

        $data = [];

        foreach ($submissions['rows'] as $row) {
            $d = ['id' => $row->id];
            foreach($submissions['fields'] as $name => $type) {
                $r  = (array)$row;
                unset($r['ip_address']);
                unset($r['timestamp']);
                unset($r['form_id']);
                if (isset($r[$name])) {
                    $d[$name] = (is_array($r[$name])) ? implode(', ', $r[$name]) : $r[$name];
                } else {
                    $d[$name] = '';
                }
            }
            $d['ip_address'] = $row->ip_address;
            $d['timestamp']  = $row->timestamp;
            $data[] = $d;
        }

        $data = new Data($data);
        $data->serialize('csv');
        $data->outputToHttp($_SERVER['HTTP_HOST'] . '_' . str_replace(' ', '_', strtolower($form->name)) . '_' . date('Y-m-d') . '.csv');
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
        $this->sess->setRequestValue('removed', true);
        $this->redirect(BASE_PATH . APP_URI . '/forms');
    }

    /**
     * Process action method
     *
     * @return void
     */
    public function process()
    {
        if ($this->request->isPost()) {
            $submission = new Model\FormSubmission();
            $submission->process($this->request->getPost());
        }
        if (null !== $this->request->getPost('id')) {
            $this->sess->setRequestValue('removed', true);
            $this->redirect(BASE_PATH . APP_URI . '/forms/submissions/' . $this->request->getPost('id'));
        } else {
            $this->redirect(BASE_PATH . APP_URI . '/forms');
        }
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
