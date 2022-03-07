<?php namespace Zephyrus\Network\HttpRequester\RequestTypes;

use InvalidArgumentException;
use Zephyrus\Exceptions\HttpRequesterException;
use Zephyrus\Network\HttpRequester\MultiPartFormPayload;
use Zephyrus\Utilities\FileSystem\File;

trait HttpUploadRequest
{
    public function upload(string $filePath, string $name = 'file', ?string $uploadFilename = null, array $payload = [])
    {
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("Specified filepath [$filePath] is not readable and thus cannot be prepared as a remote request file transfer.");
        }
        $file = new File($filePath);
        $this->execute(array_merge([$name => $file->buildCurlFile($uploadFilename)], $payload));
    }

    /**
     * Uploads the specified file as chunks using multiple requests of same size. The receiving script should be able to
     * process the specificities of this type of upload.
     *
     * @param string $filePath
     * @param array $payload
     * @param callable|null $callback
     * @param int $chuckSize
     * @throws HttpRequesterException
     */
    public function uploadAsChunks(string $filePath, array $payload = [], ?callable $callback = null, int $chuckSize = 1024000 * 20)
    {
        if (!is_readable($filePath)) {
            throw new InvalidArgumentException("Specified filepath [$filePath] is not readable and thus cannot be prepared as a remote request file transfer.");
        }
        if ($chuckSize < 1024000) {
            throw new InvalidArgumentException("Upload chunk size must be at least 1MB.");
        }

        $uuid = uniqid();
        $chunk = 1;
        $offset = 0;
        $totalFileSize = filesize($filePath);
        $filename = pathinfo($filePath, PATHINFO_BASENAME);
        $totalChunks = ($totalFileSize % $chuckSize == 0)
            ? (int) ($totalFileSize / $chuckSize)
            : (int) (($totalFileSize / $chuckSize) + 1);

        while ($offset < $totalFileSize) {
            $uploadPayload = new MultiPartFormPayload(array_merge($payload, [
                'upload_uuid' => $uuid,
                'upload_chunk' => $chunk,
                'upload_total_chunks' => $totalChunks,
                'upload_total_size' => $totalFileSize
            ]));
            $size = (($offset + $chuckSize) >= $totalFileSize) ? $totalFileSize - $offset : $chuckSize;
            $data = file_get_contents($filePath, false, null, $offset, $size);
            $uploadPayload->uploadData($data, $filename);
            $this->setContentType($uploadPayload->getContentType());
            $result = $this->execute($uploadPayload->getContent());
            if (!is_null($callback)) {
                ($callback)($result, $chunk, $totalChunks);
            }
            $offset += $chuckSize;
            $chunk++;
        }
    }
}
