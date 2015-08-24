<?php
/**
 * Module Name: phire-forms
 * Author: Nick Sagona
 * Description: This is the forms module for Phire CMS 2, to be used in conjunction with the fields module
 * Version: 1.0
 */
return [
    'phire-forms' => [
        'prefix'     => 'Phire\Forms\\',
        'src'        => __DIR__ . '/../src',
        'routes'     => include 'routes.php',
        'resources'  => include 'resources.php',
        'forms'      => include 'forms.php',
        'nav.phire'  => [
            'forms' => [
                'name' => 'Forms',
                'href' => '/forms',
                'acl' => [
                    'resource'   => 'forms',
                    'permission' => 'index'
                ],
                'attributes' => [
                    'class' => 'forms-nav-icon'
                ]
            ]
        ],
        'models' => [
            'Phire\Forms\Model\Form' => []
        ],
        'events' => [
            [
                'name'     => 'app.route.pre',
                'action'   => 'Phire\Forms\Event\Form::bootstrap',
                'priority' => 1000
            ],
            [
                'name'     => 'app.send.pre',
                'action'   => 'Phire\Forms\Event\Form::parseTemplate',
                'priority' => 1000
            ]
        ]
    ]
];
