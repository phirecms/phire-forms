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

}
