<?php

return [
    APP_URI => [
        '/forms[/]' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'index',
            'acl'        => [
                'resource'   => 'forms',
                'permission' => 'index'
            ]
        ],
        '/forms/add[/]' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'add',
            'acl'        => [
                'resource'   => 'forms',
                'permission' => 'add'
            ]
        ],
        '/forms/edit/:id' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'edit',
            'acl'        => [
                'resource'   => 'forms',
                'permission' => 'edit'
            ]
        ],
        '/forms/remove[/]' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'remove',
            'acl'        => [
                'resource'   => 'forms',
                'permission' => 'remove'
            ]
        ]
    ]
];
