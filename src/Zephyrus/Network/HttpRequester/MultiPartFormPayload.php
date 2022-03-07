<?php namespace Zephyrus\Network\HttpRequester;

use Zephyrus\Network\ContentType;

class MultiPartFormPayload
{
    private string $boundary;
    private array $formData = [];

    public function __construct(array $payload = [])
    {
        $this->buildFormDataFromPayload($payload);
    }

    public function uploadData(string $data, string $filename, string $name = "upload_file", string $contentType = ContentType::APPLICATION)
    {
        $this->formData[] = implode("\r\n", [
            "Content-Disposition: form-data; name=\"$name\"; filename=\"$filename\"",
            "Content-Type: $contentType",
            "",
            $data
        ]);
    }

    public function getContentType(): string
    {
        $this->generateSafeBoundary();
        return ContentType::FORM_MULTIPART . "; boundary=$this->boundary";
    }

    public function getContent(): string
    {
        $this->injectBoundary();
        return implode("\r\n", $this->formData);
    }

    private function generateSafeBoundary()
    {
        do {
            $boundary = "---------------------" . md5(mt_rand() . microtime());
        } while (preg_grep("/{$boundary}/", $this->formData));
        $this->boundary = $boundary;
    }

    private function injectBoundary()
    {
        $boundary = $this->boundary;
        array_walk($this->formData, function (&$part) use ($boundary) {
            $part = "--{$boundary}\r\n{$part}";
        });
        $this->formData[] = "--{$boundary}--";
        $this->formData[] = "";
    }

    private function buildFormDataFromPayload(array $payload)
    {
        foreach ($payload as $name => $value) {
            $this->formData[] = implode("\r\n", [
                "Content-Disposition: form-data; name=\"$name\"",
                "",
                $value
            ]);
        }
    }
}
