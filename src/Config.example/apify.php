<?php

return [
    'enable' => true,
    'logging' => true,
    'api_token_field' => 'api_token',
    'users' => [
        [
            'token' => 'full',
            'permissions' => [
                '*' => ['create', 'read', 'update', 'delete', 'raw']
            ]
        ],[
            'token' => 'read',
            'permissions' => [
                '*' => ['read']
            ]
        ],
        [
            'token' => '',
            'permissions' => [

            ]
        ]
    ]
];
