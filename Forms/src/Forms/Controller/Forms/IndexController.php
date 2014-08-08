<?php
/**
 * @namespace
 */
namespace Forms\Controller\Forms;

use Pop\Filter\String;
use Pop\Http\Response;
use Pop\Http\Request;
use Pop\Project\Project;
use Phire\Controller\AbstractController;
use Forms\Form;
use Forms\Model;
use Forms\Table;

class IndexController extends AbstractController
{

    /**
     * Constructor method to instantiate the fields controller object
     *
     * @param  Request  $request
     * @param  Response $response
     * @param  Project  $project
     * @param  string   $viewPath
     * @return self
     */
    public function __construct(Request $request = null, Response $response = null, Project $project = null, $viewPath = null)
    {
        if (null === $viewPath) {
            $cfg = $project->module('Forms')->asArray();
            $viewPath = __DIR__ . '/../../../../view/forms';

            if (isset($cfg['view'])) {
                $class = get_class($this);
                if (is_array($cfg['view']) && isset($cfg['view'][$class])) {
                    $viewPath = $cfg['view'][$class];
                } else if (is_array($cfg['view']) && isset($cfg['view']['*'])) {
                    $viewPath = $cfg['view']['*'];
                } else if (is_string($cfg['view'])) {
                    $viewPath = $cfg['view'];
                }
            }
        }

        parent::__construct($request, $response, $project, $viewPath);
    }

    /**
     * Form index method
     *
     * @return void
     */
    public function index()
    {
        $this->prepareView('index.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->set('title', $this->view->i18n->__('Forms'));

        $formType = new Model\Form(array('acl' => $this->project->getService('acl')));
        $formType->getAll($this->request->getQuery('sort'), $this->request->getQuery('page'));
        $this->view->set('table', $formType->table);
        $this->send();
    }

    /**
     * Form add method
     *
     * @return void
     */
    public function add()
    {
        $this->prepareView('index.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav'),
        ));

        $this->view->set('title', $this->view->i18n->__('Forms') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Add'));

        $form = new Form\FormType(
            $this->request->getBasePath() . $this->request->getRequestUri(), 'post'
        );
        if ($this->request->isPost()) {
            $form->setFieldValues(
                $this->request->getPost(),
                array('htmlentities' => array(ENT_QUOTES, 'UTF-8'))
            );

            if ($form->isValid()) {
                $formType = new Model\Form();
                $formType->save($form);
                if (null !== $this->request->getPost('update_value') && ($this->request->getPost('update_value') == '1')) {
                    Response::redirect($this->request->getBasePath() . '/edit/' . $formType->id . '?saved=' . time());
                } else if (null !== $this->request->getQuery('update')) {
                    $this->sendJson(array(
                        'redirect' => $this->request->getBasePath() . '/edit/' . $formType->id . '?saved=' . time(),
                        'updated'  => ''
                    ));
                } else {
                    Response::redirect($this->request->getBasePath() . '?saved=' . time());
                }
            } else {
                if (null !== $this->request->getQuery('update')) {
                    $this->sendJson($form->getErrors());
                } else {
                    $this->view->set('form', $form);
                    $this->send();
                }
            }
        } else {
            $this->view->set('form', $form);
            $this->send();
        }
    }

    /**
     * Form edit method
     *
     * @return void
     */
    public function edit()
    {
        if (null === $this->request->getPath(1)) {
            Response::redirect($this->request->getBasePath());
        } else {
            $this->prepareView('index.phtml', array(
                'assets'   => $this->project->getAssets(),
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav'),
            ));

            $formType = new Model\Form();
            $formType->getById($this->request->getPath(1));

            // If field is found and valid
            if (isset($formType->id)) {
                $this->view->set('title', $this->view->i18n->__('Forms') . ' ' . $this->view->separator . ' ' . $formType->name)
                           ->set('data_title', $this->view->i18n->__('Extensions') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Modules') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Forms') . ' ' . $this->view->separator . ' ');
                $form = new Form\FormType(
                    $this->request->getBasePath() . $this->request->getRequestUri(), 'post'
                );

                // If form is submitted
                if ($this->request->isPost()) {
                    $form->setFieldValues(
                        $this->request->getPost(),
                        array('htmlentities' => array(ENT_QUOTES, 'UTF-8'))
                    );

                    // If form is valid, save field
                    if ($form->isValid()) {
                        $formType->update($form);
                        if (null !== $this->request->getPost('update_value') && ($this->request->getPost('update_value') == '1')) {
                            Response::redirect($this->request->getBasePath() . '/edit/' . $formType->id . '?saved=' . time());
                        } else if (null !== $this->request->getQuery('update')) {
                            $this->sendJson(array(
                                'updated' => ''
                            ));
                        } else {
                            Response::redirect($this->request->getBasePath() . '?saved=' . time());
                        }
                    // Else, re-render the form with errors
                    } else {
                        if (null !== $this->request->getQuery('update')) {
                            $this->sendJson($form->getErrors());
                        } else {
                            $this->view->set('form', $form);
                            $this->send();
                        }
                    }
                // Else, render form
                } else {
                    $form->setFieldValues(
                        $formType->getData(null, false)
                    );
                    $this->view->set('form', $form);
                    $this->send();
                }
            // Else, redirect
            } else {
                Response::redirect($this->request->getBasePath());
            }
        }
    }

    /**
     * Form view submissions method
     *
     * @return void
     */
    public function view()
    {
        if (null === $this->request->getPath(1)) {
            Response::redirect($this->request->getBasePath());
        } else {
            $this->prepareView('submissions.phtml', array(
                'assets'   => $this->project->getAssets(),
                'acl'      => $this->project->getService('acl'),
                'phireNav' => $this->project->getService('phireNav'),
            ));

            $formType = new Model\Form(array('acl' => $this->project->getService('acl')));
            $formType->getById($this->request->getPath(1));

            // If form is found and valid
            if (isset($formType->id)) {
                if ((null !== $this->request->getPath(2)) && is_numeric($this->request->getPath(2))) {
                    $formType->getSubmission($this->request->getPath(2));
                    $this->view->set('title', $this->view->i18n->__('Forms') . ' ' . $this->view->separator . ' ' . $formType->name . ' ' . $this->view->separator . ' ' . $formType->submitDate)
                               ->set('formId', $formType->formId)
                               ->set('ipAddress', $formType->ipAddress)
                               ->set('submitDate', $formType->submitDate)
                               ->set('submission', $formType->submission);
                    $this->send();
                } else {
                    $formType->getSubmissions($this->request->getQuery('sort'), $this->request->getQuery('page'));
                    $this->view->set('title', $this->view->i18n->__('Forms') . ' ' . $this->view->separator . ' ' . $formType->name . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Submissions'))
                               ->set('formId', $formType->id)
                               ->set('table', $formType->table);
                    $this->send();
                }
            // Else, redirect
            } else {
                Response::redirect($this->request->getBasePath());
            }
        }

    }

    /**
     * Form export method
     *
     * @return void
     */
    public function export()
    {
        if (null === $this->request->getPath(1)) {
            Response::redirect($this->request->getBasePath());
        } else {
            $formType = new Model\Form();
            $formType->getExport($this->request->getPath(1));

            if (isset($formType->rows[0])) {
                \Pop\Data\Data::factory($formType->rows)->writeData($_SERVER['HTTP_HOST'] . '_' . String::slug($formType->formName) . '_' . date('Y-m-d') . '.csv', true, true);
            } else {
                Response::redirect($this->request->getBasePath());
            }
        }
    }

    /**
     * Form remove method
     *
     * @return void
     */
    public function remove()
    {
        // Loop through and delete the fields
        if ($this->request->isPost()) {
            $formType = new Model\Form();
            $formType->remove($this->request->getPost());
        }

        if (null !== $this->request->getPath(1)) {
            Response::redirect($this->request->getBasePath() . '/view/' . $this->request->getPath(1) . '?removed=' . time());
        } else {
            Response::redirect($this->request->getBasePath() . '?removed=' . time());
        }
    }

    /**
     * Error method
     *
     * @return void
     */
    public function error()
    {
        $this->prepareView('error.phtml', array(
            'assets'   => $this->project->getAssets(),
            'acl'      => $this->project->getService('acl'),
            'phireNav' => $this->project->getService('phireNav')
        ));

        $this->view->set('title', $this->view->i18n->__('404 Error') . ' ' . $this->view->separator . ' ' . $this->view->i18n->__('Page Not Found'))
                   ->set('msg', $this->view->error_message);
        $this->send(404);
    }

}

