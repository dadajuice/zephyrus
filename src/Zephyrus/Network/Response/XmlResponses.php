<?php namespace Zephyrus\Network\Response;

use Exception;
use SimpleXMLElement;
use Zephyrus\Exceptions\XmlParseException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait XmlResponses
{
    /**
     * Renders the given data as XML. The data can be a direct SimpleXMLElement or simply an associative array. If an
     * array is provided, the root element must be explicitly given.
     *
     * @param array | SimpleXMLElement | string $data
     * @param string $rootElement
     * @return Response
     * @throws XmlParseException
     */
    public function xml(array|SimpleXMLElement|string $data, string $rootElement = ""): Response
    {
        $response = new Response(ContentType::XML);
        if (is_string($data)) {
            try {
                $data = new SimpleXMLElement($data);
            } catch (Exception $e) {
                throw new XmlParseException($data, $e->getMessage());
            }
        }
        if ($data instanceof SimpleXMLElement) {
            $response->setContent($data->asXML());
        }
        if (is_array($data)) {
            try {
                $xml = new SimpleXMLElement('<' . $rootElement . '/>');
            } catch (Exception $e) {
                throw new XmlParseException($data, $e->getMessage());
            }
            self::arrayToXml($data, $xml);
            $response->setContent($xml->asXML());
        }
        return $response;
    }

    private function arrayToXml(array $data, SimpleXMLElement $xml): void
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'node' . $key;
            }
            if (is_array($value)) {
                $subNode = $xml->addChild($key);
                self::arrayToXml($value, $subNode);
                return;
            }
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}
