<?php namespace Zephyrus\Network\HttpRequester\RequestTypes;

use InvalidArgumentException;

trait HttpDownloadRequest
{
    public function download(string|array $payload = "", ?string $filePath = null): string
    {
        if (is_null($filePath)) {
            $filePath = tempnam(sys_get_temp_dir(), "zephyrus");
        }
        if (!is_writable($filePath)) {
            throw new InvalidArgumentException("Cannot open the given file path [$filePath] to hold the downloaded file.");
        }
        $file = @fopen($filePath, 'w+');
        $this->addOption(CURLOPT_TIMEOUT, 28800);
        $this->addOption(CURLOPT_FILE, $file);
        $this->execute($payload);
        fclose($file);
        return $filePath;
    }

    public function passthroughDownload(string|array $payload = "")
    {
        $this->addOption(CURLOPT_TIMEOUT, 500);
        $this->setWriteCallback(function ($curl, $data) {
            echo $data;
            return strlen($data);
        });
        $this->execute($payload);
    }
}
