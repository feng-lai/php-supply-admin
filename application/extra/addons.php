<?php

return [
    'autoload' => false,
    'hooks' => [
        'app_init' => [
            'cos',
        ],
        'module_init' => [
            'cos',
            'third',
        ],
        'upload_config_init' => [
            'cos',
        ],
        'upload_delete' => [
            'cos',
        ],
        'epay_config_init' => [
            'epay',
        ],
        'addon_action_begin' => [
            'epay',
        ],
        'action_begin' => [
            'epay',
            'third',
        ],
        'admin_login_init' => [
            'loginbg',
        ],
        'config_init' => [
            'qcloudsms',
            'summernote',
            'third',
        ],
        'sms_send' => [
            'qcloudsms',
        ],
        'sms_notice' => [
            'qcloudsms',
        ],
        'sms_check' => [
            'qcloudsms',
        ],
        'user_delete_successed' => [
            'third',
        ],
        'user_logout_successed' => [
            'third',
        ],
        'view_filter' => [
            'third',
        ],
    ],
    'route' => [
        '/example$' => 'example/index/index',
        '/example/d/[:name]' => 'example/demo/index',
        '/example/d1/[:name]' => 'example/demo/demo1',
        '/example/d2/[:name]' => 'example/demo/demo2',
        '/third$' => 'third/index/index',
        '/third/connect/[:platform]' => 'third/index/connect',
        '/third/callback/[:platform]' => 'third/index/callback',
        '/third/bind/[:platform]' => 'third/index/bind',
        '/third/unbind/[:platform]' => 'third/index/unbind',
    ],
    'priority' => [],
    'domain' => '',
];
