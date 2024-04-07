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

    /**
     * The queue to use when uploading files.
     * If null, the upload will not be queued.
     * Note: The `afterUploadUsing` method won't be called when the upload is queued.
     * The `afterUpload` method will still be called but the request object will be a new request object
     * that doesn't include the file uploads. This is because UploadedFile objects are not serializable.
     */
    'upload_on_queue_using' => null,

    /**
     * The mime types allowed for the uploads.
     */
    'mimes' => [
        'image'     => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp'],
        'video'     => ['mp4', 'avi', 'mov', 'wmv', 'flv', '3gp', 'mkv'],
        'document'  => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt']
    ]
];
