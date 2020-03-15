<?php namespace Zephyrus\Network;

use Zephyrus\Network\Responses\AbortResponses;
use Zephyrus\Network\Responses\RenderResponses;
use Zephyrus\Network\Responses\StreamResponses;
use Zephyrus\Network\Responses\XmlResponses;

class ResponseFactory
{
    /**
     * @var ResponseFactory
     */
    private static $instance = null;

    use AbortResponses;
    use RenderResponses;
    use StreamResponses;
    use XmlResponses;

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function buildRedirect(string $url): Response
    {
        $response = new Response(ContentType::PLAIN, 303);
        $response->addHeader('Location', $url);
        return $response;
    }

    public function buildJson($data): Response
    {
        $response = new Response(ContentType::JSON);
        $response->setContent(json_encode($data));
        return $response;
    }

    public function buildPlain($data): Response
    {
        $response = new Response(ContentType::PLAIN);
        $response->setContent($data);
        return $response;
    }

    public function buildDownload(string $filePath, ?string $filename = null, bool $deleteAfter = false): Response
    {
        if (is_null($filename)) {
            $filename = basename($filePath);
        }
        $contentLength = filesize($filePath);
        $response = new Response(ContentType::APPLICATION);
        $response->addHeader("Pragma", "public");
        $response->addHeader("Expires", "0");
        $response->addHeader("Cache-Control", "must-revalidate, post-check=0, pre-check=0");
        $response->addHeader("Cache-Control", "public");
        $response->addHeader("Content-Description", "File Transfer");
        $response->addHeader("Content-Disposition", 'attachment; filename="' . $filename . '"');
        $response->addHeader("Content-Transfer-Encoding", "binary");
        $response->addHeader("Content-Length", $contentLength);
        $response->setContentCallback(function () use ($filePath, $deleteAfter) {
            @readfile($filePath);
            if ($deleteAfter) {
                unlink($filePath);
            }
        });
        return $response;
    }
}
