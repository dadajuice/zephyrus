<?php namespace Zephyrus\Network;

use Zephyrus\Network\Response\AbortResponses;
use Zephyrus\Network\Response\DownloadResponses;
use Zephyrus\Network\Response\RenderResponses;
use Zephyrus\Network\Response\StreamResponses;
use Zephyrus\Network\Response\SuccessResponse;
use Zephyrus\Network\Response\XmlResponses;

class ResponseBuilder
{
    use AbortResponses;
    use RenderResponses;
    use StreamResponses;
    use SuccessResponse;
    use XmlResponses;
    use DownloadResponses;
}
