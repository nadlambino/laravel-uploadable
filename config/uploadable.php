<?php

return [
    'env' => [
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
];
