<?php

return [

    'sign_up' => [
        'release_token' => env('SIGN_UP_RELEASE_TOKEN'),
        'validation_rules' => [
            'name' => 'required',
            'stu_code' => 'required',
            'password' => 'required'
        ]
    ],

    'login' => [
        'validation_rules' => [
            'user_name' => 'required',
            'password' => 'required'
        ]
    ],

    'forgot_password' => [
        'validation_rules' => [
            'stu_code' => 'required'
        ]
    ],

    'reset_password' => [
        'release_token' => env('PASSWORD_RESET_RELEASE_TOKEN', false),
        'validation_rules' => [
            //'token' => 'required',
            'stu_code' => 'required',
            'password' => 'required|confirmed'
        ]
    ],
    'change_password' => [
        'validation_rules' => [
            'stu_id'=>'required',
            'current_password' => 'required',
            'new_password' => 'required|confirmed'
        ]
    ]

];
