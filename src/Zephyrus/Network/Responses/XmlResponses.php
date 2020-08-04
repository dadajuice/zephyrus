<?php namespace Zephyrus\Network\Responses;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;

trait XmlResponses
{
    /**
     * Renders the given data as XML. The data can be a direct SimpleXMLElement
     * or simply an associative array. If an array is provided, the root
     * element must be explicitly given.
     *
     * @param array | \SimpleXMLElement $data
     * @param string $root
     * @return Response
     */
    public function xml($data, $root = ""): Response
    {
        $response = new Response(ContentType::XML);
        if ((!$data instanceof \SimpleXMLElement) && !is_array($data)) {
            throw new \RuntimeException("Cannot parse specified data as XML");
        }
        if ($data instanceof \SimpleXMLElement) {
            $response->setContent($data->asXML());
        }
        if (is_array($data)) {
            $xml = new \SimpleXMLElement('<' . $root . '/>');
            $this->arrayToXml($data, $xml);
            $response->setContent($xml->asXML());
        }
        return $response;
    }

    private function arrayToXml($data, \SimpleXMLElement &$xml)
    {
        foreach ($data as $key => $value) {
            if (is_numeric($key)) {
                $key = 'node' . $key;
            }
            if (is_array($value)) {
                $subnode = $xml->addChild($key);
                $this->arrayToXml($value, $subnode);
                return;
            }
            $xml->addChild("$key", htmlspecialchars("$value"));
        }
    }
}
