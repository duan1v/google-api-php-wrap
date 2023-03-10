<?php

return [
    'date_format' => 'd-M-Y',

    'default' => 'default',

    'accounts' => [
        'default' => [
            'user'            => 'XXXXXX@gmail.com',
            'cache_path'      => __DIR__ . '/../cache',
            'auth_path'       => __DIR__ . '/../auth',
            'credential_file' => 'credentials.json',
            'token_file'      => 'token.json',
            'project_name'    => 'test',
            'file_manager'    => \Dywily\Gaw\FileManager::class,
        ],
    ],
];
