<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    |
    | Enable or disable the package's validation rules. Set to false if validation
    | has been performed separately, such as in a form request.
    |
    */
    'validate' => true,

    /*
    |--------------------------------------------------------------------------
    | Delete Model on Upload Failure
    |--------------------------------------------------------------------------
    |
    | Automatically delete the newly created model if the upload process fails.
    | Applicable only to models that are being created.
    |
    */
    'delete_model_on_upload_fail' => true,

    /*
    |--------------------------------------------------------------------------
    | Rollback Model on Upload Failure
    |--------------------------------------------------------------------------
    |
    | Revert changes made to an existing model if the upload fails, restoring
    | the model's original attributes. Applies only to updated models.
    |
    */
    'rollback_model_on_upload_fail' => true,

    /*
    |--------------------------------------------------------------------------
    | Force Delete Uploads
    |--------------------------------------------------------------------------
    |
    | Determines whether uploaded files are permanently deleted. By default,
    | files are soft deleted, allowing for recovery.
    |
    */
    'force_delete_uploads' => false,

    /*
    |--------------------------------------------------------------------------
    | Replace Previous Uploads
    |--------------------------------------------------------------------------
    |
    | Determines whether uploaded files should be replaced with new ones. If
    | false, new files will be uploaded. If true, previous files will be
    | deleted once the new ones are uploaded.
    |
    */
    'replace_previous_uploads' => false,

    /*
    |--------------------------------------------------------------------------
    | Upload Queue
    |--------------------------------------------------------------------------
    |
    | Specify the queue name for uploading files. If set to null, uploads are
    | processed immediately. Otherwise, files are queued and processed.
    |
    */
    'upload_on_queue' => null,

    /*
    |--------------------------------------------------------------------------
    | Delete Model on Queued Upload Failure
    |--------------------------------------------------------------------------
    |
    | Delete the newly created model if a queued upload fails. Only affects models
    | that are being created.
    |
    */
    'delete_model_on_queue_upload_fail' => false,

    /*
    |--------------------------------------------------------------------------
    | Rollback Model on Queued Upload Failure
    |--------------------------------------------------------------------------
    |
    | Revert changes to a model if a queued upload fails, using the model's original
    | attributes before the upload started. Affects only updated models.
    |
    */
    'rollback_model_on_queue_upload_fail' => false,

    /*
    |--------------------------------------------------------------------------
    | Temporary Storage Disk
    |--------------------------------------------------------------------------
    |
    | Define the disk for temporary file storage during queued uploads. This
    | is where files are stored before being processed.
    |
    */
    'temporary_disk' => 'local',

    /*
    |--------------------------------------------------------------------------
    | Temporary URL
    |--------------------------------------------------------------------------
    |
    | Temporary URL for files that are uploaded locally is not supported by the
    | local disk. This setting allows you to specify the path and middleware to
    | access the files temporarily which uses a signed URL under the hood.
    |
    */
    'temporary_url' => [
        'path' => '/temporary',
        'middleware' => ['signed'],
        'expiration' => '+5 minutes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Mimes by Extension
    |--------------------------------------------------------------------------
    |
    | Specify the mime types by extension that is allowed for uploads. Supports
    | categorization for images, videos, and documents with specific file
    | extensions.
    |
    */
    'mimes' => [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp', 'ico'],
        'video' => ['mp4', 'webm', 'avi', 'mov', 'wmv', 'flv', '3gp', 'mkv', 'mpg', 'mpeg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    ],
];
