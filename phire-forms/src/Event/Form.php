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
     * Parse form object in a template
     *
     * @param  AbstractController $controller
     * @param  Application        $application
     * @return void
     */
    public static function parseTemplate(AbstractController $controller, Application $application)
    {
        if ($application->isRegistered('phire-templates') &&
            ($controller instanceof \Phire\Content\Controller\IndexController) && ($controller->hasView()) &&
            ($controller->view()->isStream())) {
            if (strpos($controller->view()->getTemplate()->getTemplate(), '[{form_') !== false) {
                // Parse any form placeholders

                $template = $controller->view()->getTemplate()->getTemplate();
                $formIds = [];
                $forms   = [];
                preg_match_all('/\[\{form.*\}\]/', $template, $forms);
                if (isset($forms[0]) && isset($forms[0][0])) {
                    foreach ($forms[0] as $form) {
                        $id = substr($form, (strpos($form, 'form_') + 5));
                        $formIds[] = str_replace('}]', '', $id);
                    }
                }

                if (count($formIds) > 0) {
                    foreach ($formIds as $id) {
                        $form = new \Phire\Forms\Form\Form($id);
                        if ($form->isSubmitted()) {
                            $values = ($form->getMethod() == 'post') ? $_POST : $_GET;
                            $form->addFilter('strip_tags');
                            $form->setFieldValues($values);
                            if ($form->isValid()) {
                                $form->process();
                                $template = str_replace('[{form_' . $id . '}]', $form->getMessage(), $template);
                            } else {
                                $template = str_replace('[{form_' . $id . '}]', (string)$form, $template);
                            }
                        } else {
                            $template = str_replace('[{form_' . $id . '}]', (string)$form, $template);
                        }
                    }
                }

                $controller->view()->getTemplate()->setTemplate($template);
            }
        }
    }

}
