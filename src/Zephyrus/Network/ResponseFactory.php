<?php namespace Zephyrus\Network;

use Zephyrus\Network\Responses\AbortResponses;
use Zephyrus\Network\Responses\DownloadResponses;
use Zephyrus\Network\Responses\RenderResponses;
use Zephyrus\Network\Responses\StreamResponses;
use Zephyrus\Network\Responses\SuccessResponse;
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
    use SuccessResponse;
    use XmlResponses;
    use DownloadResponses;

    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}
