<?php

return [

    /**
     * The disk to use for the uploads based on the environment.
     * The disk should be defined in the filesystems.php config file.
     */
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

    /**
     * Delete all the file uploads when the uploadable model is deleted.
     */
    'delete_uploads_on_model_delete' => true,

    /**
     * Force delete all the uploads data when the uploadable model is deleted.
     */
    'force_delete_uploads' => true,
];
