<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
        'seeds' => [
            'driver' => 'local',
            'root' => storage_path('app/seeds'),
            'url' => env('API_URL').'/seeds',
            'visibility' => 'private',
            'throw' => false,
        ],
        'cloud' => [
            'driver' => 'local',
            'root' => storage_path('app/cloud'),
            'url' => env('API_URL').'/cloud',
            'visibility' => 'public',
            'throw' => false,
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
        'migrations' => [
            'driver' => 'local',
            'root' => database_path('migrations'),
        ],
        'seeders' => [
            'driver' => 'local',
            'root' => database_path('seeders'),
        ],
        'models' => [
            'driver' => 'local',
            'root' => app_path('Models'),
        ],
        'controllers' => [
            'driver' => 'local',
            'root' => app_path('Http/Controllers'),
        ],
        'requests' => [
            'driver' => 'local',
            'root' => app_path('Http/Requests'),
        ],
        'routes' => [
            'driver' => 'local',
            'root' => base_path('routes'),
        ],
        'logs' => [
            'driver' => 'local',
            'root' => storage_path('logs'),
        ],
        'enums' => [
            'driver' => 'local',
            'root' => app_path('Enums'),
        ],
        'langs' => [
            'driver' => 'local',
            'root' => base_path('lang'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
