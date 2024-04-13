<?php

return [

    /**
     * Run the package's own validation rules.
     * You can set this to false if you want to use your own validation rules
     * or if you've already validated your uploads from your request.
     */
    'validate' => true,

    /**
     * Delete the uploadable model when the upload fails.
     * This will only work for models that are just created.
     */
    'delete_model_on_upload_fail' => true,

    /**
     * Rollback the changes made to the uploadable model when the upload fails.
     * This will only work for models that are to be updated.
     * It will update the model with the original attributes.
     */
    'rollback_model_on_upload_fail' => true,

    /**
     * By default, uploads are soft deleted.
     * Force delete all the uploads associated to the uploadable model when it's deleted.
     * When this is set to true, the uploaded file will also be deleted, otherwise, it will be kept for model restoration.
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
     */
    'upload_on_queue_using' => null,

    /**
     * Delete the uploadable model when the upload fails and the upload process is queued.
     * This will only work for models that are just created.
     */
    'delete_model_on_queue_upload_fail' => false,

    /**
     * Rollback the changes made to the uploadable model when the upload fails and the upload process is queued.
     * This will only work for models that are to be updated.
     * It will update the model with the original attributes.
     * Note that the original attributes are gotten from the model before the upload process is started.
     * So, if the model was updated after the upload process was queued, those changes will be overwritten
     * by these original attributes.
     */
    'rollback_model_on_queue_upload_fail' => false,

    /**
     * The disk to use to store the files temporarily when upload process is queued.
     */
    'temp_disk' => 'local',

    /**
     * The mime types allowed for the uploads.
     */
    'mimes' => [
        'image' => ['jpeg', 'jpg', 'png', 'gif', 'bmp', 'svg', 'webp'],
        'video' => ['mp4', 'avi', 'mov', 'wmv', 'flv', '3gp', 'mkv'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt'],
    ],
];
