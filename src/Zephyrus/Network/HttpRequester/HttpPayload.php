<?php namespace Zephyrus\Network\HttpRequester;

use Zephyrus\Network\ContentType;

class HttpPayload
{
    private string $contentType;
    private string|array $originalPayload;
    private string|array $payload;

    public function __construct(string $contentType, string|array $payload)
    {
        $this->contentType = $contentType;
        $this->originalPayload = $payload;
        $this->initializePayload();
    }

    public function getContent()
    {
        return $this->payload;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    private function initializePayload()
    {
        // If a cURL file instance is detected within the payload (defined as an array), the content type should be
        // multipart/form-data and the payload should be properly formatted for cURL to use it.
        if (is_array($this->originalPayload) && $this->hasCurlFile($this->originalPayload)) {
            $this->contentType = ContentType::FORM_MULTIPART;
            $this->payload = $this->prepareMultipartFormData($this->originalPayload);
            return;
        }

        // If the payload is an array, the payload should be transformed as a string using the http_build_query.
        if (is_array($this->originalPayload)) {
            $this->payload = http_build_query($this->originalPayload);
            return;
        }

        // Payload is already a string and thus doesn't need any more processing (e.g. JSON, XML content type)
        $this->payload = $this->originalPayload;
    }

    /**
     * Verifies if the payload (only array compatible) has a CURLFile instance within its data. If it happens, it means
     * the request needs to be a multipart/form-data content type.
     *
     * @param array $payload
     * @return bool
     */
    private function hasCurlFile(array $payload): bool
    {
        foreach ($payload as $data) {
            if (is_array($data) && $this->hasCurlFile($data)) {
                return true;
            }
            if ($data instanceof \CURLFile) {
                return true;
            }
        }
        return false;
    }

    /**
     * Corrects a problem with cURL while sending array in multipart/form-data. Reconstruct an array with the proper
     * formatting needed by multipart content type. Can go up to 2 levels of nested array. Needs to be done recursively
     * to allow an unlimited amount of levels.
     *
     * @param array $payload
     * @return array
     */
    private function prepareMultipartFormData(array $payload): array
    {
        $parameters = [];
        foreach ($payload as $parameterName => $parameterValue) {
            // Problem with cURL while sending array in multipart/form-data
            if (is_array($parameterValue)) {
                foreach ($parameterValue as $key => $value) {
                    if (is_array($value)) {
                        // Nested array case (2 levels)
                        foreach ($value as $innerKey => $innerValue) {
                            $parameters[$parameterName . '[' . $key . '][' . $innerKey . ']'] = $innerValue;
                        }
                    } else {
                        $parameters[$parameterName . '[' . $key . ']'] = $value;
                    }
                }
                continue;
            }
            $parameters[$parameterName] = $parameterValue;
        }
        return $parameters;
    }
}
