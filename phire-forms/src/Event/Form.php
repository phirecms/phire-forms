<?php

namespace Phire\Forms\Event;

use Phire\Forms\Table;
use Pop\Application;
use Pop\Web\Mobile;
use Pop\Web\Session;
use Phire\Controller\AbstractController;

class Form
{

    /**
     * Bootstrap the module
     *
     * @param  Application $application
     * @return void
     */
    public static function bootstrap(Application $application)
    {
        $config = $application->module('phire-forms');
        $models = (isset($config['models'])) ? $config['models'] : null;
        $forms  = Table\Forms::findAll();

        foreach ($forms->rows() as $form) {
            if (null !== $models) {
                if (!isset($models['Phire\Forms\Model\Form'])) {
                    $models['Phire\Forms\Model\Form'] = [];
                }

                $models['Phire\Forms\Model\Form'][] = [
                    'type_field' => 'id',
                    'type_value' => $form->id,
                    'type_name'  => $form->name
                ];
            }
        }

        if (null !== $models) {
            $application->module('phire-forms')->mergeConfig(['models' => $models]);
        }
    }

    /**
     * Parse form object
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function parseForms(AbstractController $controller, Application $application)
    {
        if ($controller->hasView() &&
            (($application->isRegistered('phire-content') && ($controller instanceof \Phire\Content\Controller\IndexController)) ||
             ($application->isRegistered('phire-categories') && ($controller instanceof \Phire\Categories\Controller\IndexController)))) {
            $body = $controller->response()->getBody();
            if (strpos($body, '[{form_') !== false) {
                // Parse any form placeholders
                $formIds = [];
                $forms   = [];
                preg_match_all('/\[\{form.*\}\]/', $body, $forms);
                if (isset($forms[0]) && isset($forms[0][0])) {
                    foreach ($forms[0] as $form) {
                        $id        = substr($form, (strpos($form, 'form_') + 5));
                        $formIds[] = str_replace('}]', '', $id);
                    }
                }

                if (count($formIds) > 0) {
                    foreach ($formIds as $id) {
                        try {
                            $form = new \Phire\Forms\Form\Form($id);
                            if ($form->isSubmitted()) {
                                $values = ($form->getMethod() == 'post') ? $_POST : $_GET;
                                $form->addFilter('strip_tags');
                                $form->setFieldValues($values);
                                if ($form->isValid()) {
                                    $form->process();
                                    $body = str_replace('[{form_' . $id . '}]', $form->getMessage(), $body);
                                } else {
                                    $body = str_replace('[{form_' . $id . '}]', (string)$form, $body);
                                }
                            } else {
                                $body = str_replace('[{form_' . $id . '}]', (string)$form, $body);
                            }
                        } catch (\Exception $e) {
                            $body = str_replace('[{form_' . $id . '}]', '', $body);
                        }
                    }
                }

                $controller->response()->setBody($body);
            }
        }
    }

}
