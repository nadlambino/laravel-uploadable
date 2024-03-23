<?php

return [
    'disks' => [
        'local' => [
            'disk' => 'public',
            'path' => 'uploads',
            'host' => request()->getSchemeAndHttpHost(),
        ],
        'production' => [
            'disk' => 's3',
            'path' => 'uploads',
        ],
    ],

    'throw_exception' => true,
];
