<?php

return [

    /**
     * Delete the uploadable model when the upload fails.
     * This will only work for models that are just created.
     */
    'delete_model_on_upload_fail' => true,

    /**
     * Delete all the file uploads when the uploadable model is deleted.
     */
    'delete_uploads_on_model_delete' => true,

    /**
     * Force delete all the uploads data when the uploadable model is deleted.
     */
    'force_delete_uploads' => true,

    /**
     * All previous uploads will be deleted after the new uploads are saved.
     * This is to replace the old uploads with the new ones.
     */
    'delete_previous_uploads' => false,

    /**
     * The queue to use when uploading files.
     * If null, the upload will not be queued.
     * Note: The `afterUploadUsing` method won't be called when the upload is queued.
     * The `afterUpload` method will still be called but the request object will be a new request object
     * that doesn't include the file uploads. This is because UploadedFile objects are not serializable.
     */
    'upload_on_queue_using' => null,

    /**
     * Delete the uploadable model when the upload fails and the upload process is queued.
     * This will only work for models that are just created.
     */
    'delete_model_on_queue_upload_fail' => false,

    /**
     * The disk to use to store the files temporarily when upload process is queued.
     */
    'temp_disk' => 'local',

    /**
     * The mime types allowed for the uploads.
     */
    'mimes' => [
        'image'     => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp'],
        'video'     => ['mp4', 'avi', 'mov', 'wmv', 'flv', '3gp', 'mkv'],
        'document'  => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt']
    ]
];
