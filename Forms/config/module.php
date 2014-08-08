<?php
/**
 * Module Name: Forms
 * Author: Nick Sagona
 * Description: This is the Forms module for Phire 2.0. It allows for the creation, validation and submission of HTML forms via Fields.
 * Version: 1.0
 */

return array(
    'Forms' => new \Pop\Config(array(
        'base'   => realpath(__DIR__ . '/../'),
        'config' => realpath(__DIR__ . '/../config'),
        'data'   => realpath(__DIR__ . '/../data'),
        'src'    => realpath(__DIR__ . '/../src'),
        //'view'   => realpath(__DIR__ . '/../view'),
        'routes' => array(
            APP_URI => array(
                '/forms'  => 'Forms\Controller\Forms\IndexController'
            )
        ),
        'module_nav' => array(
            array(
                'name' => 'Forms',
                'href' => BASE_PATH . APP_URI . '/forms',
                'acl'  => array(
                    'resource'   => 'Forms\Controller\Forms\IndexController',
                    'permission' => 'index'
                 )
            )
        ),
        'events' => array(
            'dispatch' => array(
                'action' => function($controller) {
                    if (null !== $controller->getView()->phire) {
                        $controller->getView()->phire->loadModule('forms', 'Forms\Form\Form');
                    }
                },
                'priority' => 100
            ),
            'dispatch.send' => array(
                'action' => function($controller) {
                    if (null !== $controller->getView()->phire) {
                        if (strpos($controller->getResponse()->getBody(), '[{form_') !== false) {
                            $site = \Phire\Table\Sites::getSite();
                            $uri = $controller->getRequest()->getRequestUri();
                            if (($_SERVER['REQUEST_URI'] != $uri) && (substr($_SERVER['REQUEST_URI'], 0 - strlen($uri)) == $uri)) {
                                $uri = $_SERVER['REQUEST_URI'];
                            } else {
                                $uri = $site->base_path . $uri;
                            }
                            $result = \Forms\Model\Form::parse($controller->getResponse()->getBody(), $uri);
                            if ((substr($result, 0, 4) == 'http') || (substr($result, 0, 1) == '/')) {
                                header('Location: ' . $result);
                                exit();
                            } else {
                                $controller->getResponse()->setBody($result);
                            }
                        }
                    }
                },
                'priority' => 100
            )
        )
    ))
);

