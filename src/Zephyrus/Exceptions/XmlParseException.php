<?php namespace Zephyrus\Exceptions;

class XmlParseException extends ZephyrusException
{
    private string $rawXml;

    public function __construct(string $rawXml, string $message)
    {
        $this->rawXml = $rawXml;
        parent::__construct("XML parsing failed with message [$message]. Consult the raw data for more information.");
    }

    public function getRawXml(): string
    {
        return $this->rawXml;
    }
}
