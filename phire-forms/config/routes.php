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
        ],
        '/forms/submissions/:id' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'submissions',
            'acl'        => [
                'resource'   => 'submissions',
                'permission' => 'index'
            ]
        ],
        '/forms/submissions/export/:id' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'export',
            'acl'        => [
                'resource'   => 'submissions',
                'permission' => 'export'
            ]
        ],
        '/forms/submissions/process[/]' => [
            'controller' => 'Phire\Forms\Controller\IndexController',
            'action'     => 'process',
            'acl'        => [
                'resource'   => 'submissions',
                'permission' => 'process'
            ]
        ]
    ]
];
