<?php

return [
    'date_format' => 'd-M-Y',

    'default' => 'default',

    'accounts' => [
        'default' => [
            'user'            => 'XXXXXX@gmail.com',
            // must existed
            'cache_path'      => __DIR__ . '/../cache',
            'auth_path'       => __DIR__ . '/../auth',
            'credential_file' => 'credentials.json',
            'token_file'      => 'token.json',
            'project_name'    => 'test',
            // A temporary directory for attachments and pictures when retrieving mail
            'file_manager'    => \Dywily\Gaw\FileManager::class,
            // Templates for displaying messages, attachments and pictures
            'temp_manager'    => \Dywily\Gaw\TempManager::class,
        ],
    ],
];
