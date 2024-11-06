<?php

return [
    'relocation' => [
        'enabled' => (bool) env('FILELAYER_RELOCATION_ENABLED', false),
    ],

    'preprocessor-config' => [
        'cache' => [
            'ttl' => 60,
        ],
    ],

    'file_name_generator' => [
        'prefix' => env('FILELAYER_FILE_NAME_PREFIX', 'processed'),
        'no_actions_name' => env('FILELAYER_FILE_NAME_NO_ACTIONS', 'original'),
        'folder_1_length' => env('FILELAYER_FILE_NAME_FOLDER_1_LENGTH', 1),
        'folder_2_length' => env('FILELAYER_FILE_NAME_FOLDER_2_LENGTH', 2),
        'hash_algorithm' => env('FILELAYER_FILE_NAME_HASH_ALGORITHM', 'sha1'),
    ],

    'image_manager' => [
        'driver' => match (strtolower((string) env('FILELAYER_IMAGE_DRIVER', 'gd'))) {
            'imagick' => \Intervention\Image\Drivers\Imagick\Driver::class,
            default => \Intervention\Image\Drivers\Gd\Driver::class,
        },
        'options' => [
            'autoOrientation' => env('FILELAYER_IMAGE_AUTO_ORIENTATION', true),
            'decodeAnimation' => env('FILELAYER_IMAGE_DECODE_ANIMATION', true),
            'blendingColor' => env('FILELAYER_IMAGE_BLENDING_COLOR', 'ffffff'),
        ],
    ],
];
