<?php namespace Zephyrus\Network\Request;

class RequestAccept
{
    private string $accept;
    private array $acceptedContentTypes;

    /**
     * The Accept request HTTP header indicates which content types, expressed as MIME types, the client is able to
     * understand. The server uses content negotiation to select one of the proposals and informs the client of the
     * choice with the Content-Type response header.
     *
     * @param string $accept
     */
    public function __construct(string $accept)
    {
        $this->accept = $accept;
        $this->acceptedContentTypes = explode(',', str_replace(' ', '', $this->accept));
        $this->addDefaultPriority();
        $this->sortByPriority();
        $this->sortBySpecificity();
    }

    /**
     * Retrieves the defined accepted content type order by specified priority using the standard parameter "q" which
     * should range from 0 (lowest) to 1 (highest).
     *
     * @return array
     */
    public function getAcceptedContentTypes(): array
    {
        return array_filter(array_column($this->acceptedContentTypes, 0));
    }

    /**
     * Retrieves the raw value of the request Accept header.
     *
     * @return string
     */
    public function getAccept(): string
    {
        return $this->accept;
    }

    /**
     * When no priority parameter is given for an accepted content type, use the natural defined order by adding q=1
     * for these content types.
     */
    private function addDefaultPriority(): void
    {
        array_walk($this->acceptedContentTypes, function (&$accept) {
            // When no priority parameter is given, use natural defined order
            // by adding q=1.
            if (!str_contains($accept, ';q')) {
                $accept .= ';q=1';
            }
            $accept = explode(';q=', $accept);
        });
    }

    private function sortByPriority(): void
    {
        usort($this->acceptedContentTypes, function ($a, $b) {
            return $b[1] <=> $a[1];
        });
    }

    /**
     * Sort using specificity (*) for same priority level (e.g. mime/*).
     */
    private function sortBySpecificity(): void
    {
        usort($this->acceptedContentTypes, function ($a, $b) {
            if ($a[1] == $b[1]) {
                return substr_count($a[0], '*') <=> substr_count($b[0], '*');
            }
            return 0;
        });
    }
}
