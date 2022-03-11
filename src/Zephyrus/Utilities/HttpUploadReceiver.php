<?php namespace Zephyrus\Utilities;

use Zephyrus\Exceptions\HttpUploadException;
use Zephyrus\Network\Request;

class HttpUploadReceiver
{
    private Request $request;
    private string $uuid;
    private int $chunk;
    private int $totalChunks;
    private int $totalFileSize;
    private array $fileData;
    private int $chunkSize;
    private string $path;
    private string $originalFilename;

    public function __construct(Request $request, ?string $uploadDirectory = null)
    {
        $this->request = $request;
        $this->initializeChunkData();
        $this->initializeUploadData();
        $this->initializePath($uploadDirectory);
    }

    /**
     * @throws HttpUploadException
     */
    public function receiveChunk(callable $completedCallback): bool
    {
        if ($this->chunk > $this->totalChunks) {
            throw HttpUploadException::sizeOverflow();
        }
        $this->appendChunk();
        if ($this->chunk == $this->totalChunks) {
            $completedCallback($this->path, $this->originalFilename);
            return true;
        }
        return false;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function getChunkSize(): int
    {
        return $this->chunkSize;
    }

    public function getCompleteSize(): int
    {
        return $this->totalFileSize;
    }

    public function getChunkNumber(): int
    {
        return $this->chunk;
    }

    public function getChunkTotalCount(): int
    {
        return $this->totalChunks;
    }

    private function appendChunk()
    {
        $handler = fopen($this->path, 'a');
        fwrite($handler, $this->getChunkData());
        fclose($handler);
    }

    private function getChunkData(): string
    {
        $chunkFile = fopen($this->fileData['tmp_name'], "rb");
        return fread($chunkFile, $this->chunkSize);
    }

    private function initializePath(?string $uploadDirectory)
    {
        $uploadDirectory = $this->prepareUploadDirectory($uploadDirectory);
        $path = $uploadDirectory . DIRECTORY_SEPARATOR . $this->buildFilename();
        if ($this->chunk == 1 && file_exists($path)) {
            throw HttpUploadException::alreadyStarted();
        }
        if ($this->chunk > 1 && !file_exists($path)) {
            throw HttpUploadException::noLongerAvailable();
        }
        $this->path = $path;
    }

    private function buildFilename(): string
    {
        $fname = $this->fileData['name'];
        $extension = pathinfo($fname, PATHINFO_EXTENSION);
        $fname = pathinfo($fname, PATHINFO_FILENAME);
        $digest = md5($fname . $this->uuid);
        return $digest . (!empty($extension) ? '.' . $extension : '');
    }

    private function initializeChunkData()
    {
        $uuid = $this->request->getParameter('upload_uuid');
        $chunk = $this->request->getParameter('upload_chunk');
        $totalChunks = $this->request->getParameter('upload_total_chunks');
        $totalFileSize = $this->request->getParameter('upload_total_size');
        if (empty($uuid) || empty($chunk) || empty($totalChunks) || empty($totalFileSize)
            || !is_numeric($chunk) || !is_numeric($totalChunks) || !is_numeric($totalFileSize)) {
            throw HttpUploadException::invalidChunk();
        }
        $this->uuid = $uuid;
        $this->chunk = $chunk;
        $this->totalChunks = $totalChunks;
        $this->totalFileSize = $totalFileSize;
    }

    private function initializeUploadData()
    {
        $fileData = $this->request->getFile('upload_file');
        if (empty($fileData)) {
            throw HttpUploadException::noChunkUploaded();
        }
        if (!Validation::isUpload($fileData)) {
            throw HttpUploadException::invalidUpload();
        }
        $size = filesize($fileData['tmp_name']);
        if ($size <= 0) {
            throw HttpUploadException::emptyChunkUploaded();
        }
        $this->fileData = $fileData;
        $this->chunkSize = $size;
        $this->originalFilename = $fileData['name'];
    }

    private function prepareUploadDirectory(?string $uploadDirectory): string
    {
        if (is_null($uploadDirectory)) {
            $uploadDirectory = sys_get_temp_dir();
        }
        $uploadDirectory = rtrim($uploadDirectory, DIRECTORY_SEPARATOR);
        if (!is_writable($uploadDirectory)) {
            throw HttpUploadException::directoryNotWritable();
        }
        return $uploadDirectory;
    }
}
