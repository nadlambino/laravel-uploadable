<?php

return [
    'disks' => [
        'local' => [
            'disk' => 'public',
            'directory' => 'uploads',
            'host' => request()->getSchemeAndHttpHost(),
        ],
        'production' => [
            'disk' => 's3',
            'directory' => 'uploads',
        ],
    ],
];
