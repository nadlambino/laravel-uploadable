<?php

return [
    'env' => [
        'local' => [
            'disk' => 'public',
            'path' => 'uploads',
        ],
        'production' => [
            'disk' => 's3',
            'path' => 'uploads',
        ],
    ],
];
