<?php namespace Zephyrus\Network\Response;

use InvalidArgumentException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;
use Zephyrus\Utilities\FileSystem\File;

trait DownloadResponses
{
    /**
     * Renders a given file as an inline downloadable content (meaning it shall be viewed directly in the browser and
     * allow the client to manually download the file if needed). If no filename is given, it will automatically use
     * the actual file basename.
     *
     * @param string $filePath
     * @param string|null $filename
     * @return Response
     */
    public function downloadInline(string $filePath, ?string $filename = null): Response
    {
        if (!File::exists($filePath)) {
            throw new InvalidArgumentException("Specified file doesn't exists");
        }
        if (is_null($filename)) {
            $filename = basename($filePath);
        }

        $file = new File($filePath);
        $response = new Response($file->getMimeType(), 200);
        $response->addHeader('Cache-Control', 'public, must-revalidate, max-age=0');
        $response->addHeader('Pragma', 'public');
        $response->addHeader('Expires', 'Sat, 26 Jul 1997 05:00:00 GMT');
        $response->addHeader('Last-Modified', gmdate('D, d M Y H:i:s') . ' GMT');
        $response->addHeader("Content-Length", $file->size());
        $response->addHeader('Content-Disposition', 'inline; filename="' . $filename . '";');
        $response->setContent($file->read());
        return $response;
    }

    /**
     * Renders a given file as a downloadable content with the file's content type. If no filename is given, it will
     * automatically use the actual file basename. If the deleteAfter argument is set to true, it will automatically
     * remove the file after sending it. Useful for temporary generated files. Will download the file in chunks which
     * will allow clients to display proper download progress.
     *
     * @param string $filePath
     * @param null|string $filename
     * @param bool $deleteAfter
     * @return Response
     */
    public function download(string $filePath, ?string $filename = null, bool $deleteAfter = false): Response
    {
        if (!File::exists($filePath)) {
            throw new InvalidArgumentException("Specified file doesn't exists");
        }
        if (is_null($filename)) {
            $filename = basename($filePath);
        }

        $file = new File($filePath);
        $response = new Response($file->getMimeType(), 200);
        $this->addFileTransferHeaders($response);
        $response->addHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');
        $response->addHeader("Content-Length", $file->size());
        $response->setContentCallback(function () use ($filePath, $deleteAfter) {
            $context = stream_context_create([
                "ssl" => [
                    "verify_peer" => false,
                    "verify_peer_name" => false,
                ]
            ]);
            $handle = fopen($filePath, 'rb', false, $context);
            fpassthru($handle);
            fclose($handle);
            if ($deleteAfter) {
                unlink($filePath);
            }
        });
        return $response;
    }

    /**
     * Creates a response as a downloadable file with the specified content. By default, will send it as content type
     * application/octet-stream, but can be changed to reflect the content's nature more closely (e.g. calendar, json,
     * etc.).
     *
     * @param string $content
     * @param string $filename
     * @param string $contentType
     * @return Response
     */
    public function downloadContent(string $content, string $filename, string $contentType = ContentType::APPLICATION): Response
    {
        $contentLength = strlen($content);
        $response = new Response($contentType, 200);
        $response->setContent($content);
        $this->addFileTransferHeaders($response);
        $response->addHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');
        $response->addHeader("Content-Length", $contentLength);
        return $response;
    }

    /**
     * Creates a response as a downloadable file defined by the specified callback. Useful if the file is not located
     * on the server, but you need to serve it without download it beforehand and pass it directly to the client. For
     * this specific download type, the size, content type and filename are mandatory because it cannot be determined
     * automatically.
     *
     * @param int $size
     * @param string $contentType
     * @param string $filename
     * @param callable $callback
     * @return Response
     */
    public function downloadCallback(int $size, string $contentType, string $filename, callable $callback): Response
    {
        $response = new Response($contentType, 200);
        $this->addFileTransferHeaders($response);
        $response->addHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');
        $response->addHeader("Content-Length", $size);
        $response->setContentCallback($callback);
        return $response;
    }

    /**
     * Adds the required basic file transfer HTTP headers such as expires, pragma, cache-control, encoding, etc.
     *
     * @param Response $response
     */
    private function addFileTransferHeaders(Response $response): void
    {
        $response->addHeader("Pragma", "public");
        $response->addHeader("Expires", "0");
        $response->addHeader("Cache-Control", "must-revalidate, post-check=0, pre-check=0");
        $response->addHeader("Cache-Control", "public");
        $response->addHeader("Content-Description", "File Transfer");
        $response->addHeader("Content-Transfer-Encoding", "binary");
    }
}
