<?php
/**
 * phire-forms form configuration
 */
return [
    'Phire\Forms\Form\FormObject' => [
        [
            'submit' => [
                'type'       => 'submit',
                'value'      => 'Save',
                'attributes' => [
                    'class'  => 'save-btn wide'
                ]
            ],
            'method' => [
                'type'  => 'select',
                'label' => 'Method',
                'value' => [
                    'post' => 'POST',
                    'get' => 'GET'
                ],
                'marked' => '1'
            ],
            'filter' => [
                'type'  => 'radio',
                'label' => 'Filter',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => '1'
            ],
            'use_captcha' => [
                'type'  => 'radio',
                'label' => 'Use CAPTCHA',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => '0'
            ],
            'use_csrf' => [
                'type'  => 'radio',
                'label' => 'Use CSRF',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => '0'
            ],
            'force_ssl' => [
                'type'  => 'radio',
                'label' => 'Force SSL',
                'value' => [
                    '1' => 'Yes',
                    '0' => 'No'
                ],
                'marked' => '0'
            ],
            'id' => [
                'type'  => 'hidden',
                'value' => 0
            ]
        ],
        [
            'name' => [
                'type'       => 'text',
                'label'      => 'Name',
                'required'   => true,
                'attributes' => [
                    'size'   => 60,
                    'style'  => 'width: 98%'
                ]
            ]
        ],
        [
            'to' => [
                'type'       => 'text',
                'label'      => 'To',
                'attributes' => [
                    'size'   => 50
                ]
            ],
            'from' => [
                'type'       => 'text',
                'label'      => 'From',
                'attributes' => [
                    'size'   => 50
                ]
            ],
            'reply_to' => [
                'type'       => 'text',
                'label'      => 'Reply To',
                'attributes' => [
                    'size'   => 50
                ]
            ],
            'action' => [
                'type'       => 'text',
                'label'      => 'Action',
                'attributes' => [
                    'size'   => 50
                ]
            ]
        ],
        [
            'redirect' => [
                'type'       => 'text',
                'label'      => 'Redirect',
                'attributes' => [
                    'size'   => 50
                ]
            ],
            'attributes' => [
                'type'       => 'text',
                'label'      => 'Attributes',
                'attributes' => [
                    'size'   => 50
                ]
            ],
            'submit_value' => [
                'type'       => 'text',
                'label'      => 'Submit Value',
                'attributes' => [
                    'size'   => 50
                ]
            ],
            'submit_attributes' => [
                'type'       => 'text',
                'label'      => 'Submit Attributes',
                'attributes' => [
                    'size'   => 50
                ]
            ]
        ]
    ]
];
