<?php

namespace NadLambino\Uploadable\Concerns;

trait Validation
{
    /**
     * Get the user-defined upload rules.
     *
     * @return array<string, string|array> The rules.
     */
    protected function uploadRules() : array
    {
        return [];
    }

    /**
     * Get the upload rules messages.
     *
     * @return array<string, string> The messages.
     */
    protected function uploadRulesMessages() : array
    {
        return [];
    }

    /**
     * Get the upload rules.
     *
     * @return array<string, string|array> The rules.
     */
    protected function getUploadRules() : array
    {
        return $this->uploadRules() + [
            'document'      => ['sometimes', 'file', $documentMimesRule = $this->getDocumentMimesRule()],
            'documents.*'   => ['sometimes', 'file', $documentMimesRule],
            'image'         => ['sometimes', 'image', $imageMimesRule = $this->getImageMimesRule()],
            'images.*'      => ['sometimes', 'image', $imageMimesRule],
            'video'         => ['sometimes', $videoMimesRule = $this->getVideoMimesRule()],
            'videos.*'      => ['sometimes', $videoMimesRule],
        ];
    }

    /**
     * Get the image mimes rule.
     *
     * @return string The rule.
     */
    protected function getImageMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getImageMimes());
    }

    /**
     * Get the image mimes.
     *
     * @return array<string> The mimes.
     */
    protected function getImageMimes() : array
    {
        return config('uploadable.mimes.image');
    }

    /**
     * Get the video mimes rule.
     *
     * @return string The rule.
     */
    protected function getVideoMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getVideoMimes());
    }

    /**
     * Get the video mimes.
     *
     * @return array<string> The mimes.
     */
    protected function getVideoMimes() : array
    {
        return config('uploadable.mimes.video');
    }

    /**
     * Get the document mimes rule.
     *
     * @return string The rule.
     */
    protected function getDocumentMimesRule() : string
    {
        return 'mimes:' . implode(',', $this->getDocumentMimes());
    }

    /**
     * Get the document mimes.
     *
     * @return array<string> The mimes.
     */
    protected function getDocumentMimes() : array
    {
        return config('uploadable.mimes.document');
    }
}
