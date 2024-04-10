<?php

namespace NadLambino\Uploadable\Models\Traits;

trait UploadValidation
{
    /**
     * Add or modify rules without having to rewrite the entire rule.
     *
     * @return array<string, string|array>
     */
    protected function uploadRules() : array
    {
        return [];
    }

    /**
     * Add or modify rules' messages.
     *
     * @return array<string, string>
     */
    protected function uploadRulesMessages() : array
    {
        return [];
    }

    protected function getUploadRules() : array
    {
        return [
            'document'      => ['sometimes', 'file', $documentMimesRule = $this->getDocumentMimesRule()],
            'documents.*'   => ['sometimes', 'file', $documentMimesRule],
            'image'         => ['sometimes', 'image', $imageMimesRule = $this->getImageMimesRule()],
            'images.*'      => ['sometimes', 'image', $imageMimesRule],
            'video'         => ['sometimes', $videoMimesRule = $this->getVideoMimesRule()],
            'videos.*'      => ['sometimes', $videoMimesRule],
            ...$this->uploadRules()
        ];
    }

    protected function getImageMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getImageMimes());
    }

    protected function getImageMimes() : array
    {
        return config('uploadable.mimes.image');
    }

    protected function getVideoMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getVideoMimes());
    }

    protected function getVideoMimes() : array
    {
        return config('uploadable.mimes.video');
    }
    protected function getDocumentMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getDocumentMimes());
    }

    protected function getDocumentMimes() : array
    {
        return config('uploadable.mimes.document');
    }
}
