<?php namespace Zephyrus\Network\Request;

use Exception;
use SimpleXMLElement;
use Zephyrus\Exceptions\JsonParseException;
use Zephyrus\Exceptions\XmlParseException;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpMethod;

class RequestBody
{
    public const CUSTOM_METHOD_PARAMETER = '__method';

    private string $rawData;
    private string $contentType;
    private array $parameters = [];

    public function __construct(string $rawData, string $contentType = ContentType::FORM)
    {
        $this->rawData = $rawData;
        $this->contentType = $contentType;
        if (str_contains($contentType, ContentType::FORM)
            || str_contains($contentType, ContentType::PLAIN)) {
            $this->parameters = $this->parseUrlEncodedForm();
        }
        if (str_contains($contentType, ContentType::FORM_MULTIPART)) {
            $this->parameters = $this->parseMultiForm();
        }
        if (str_contains($contentType, ContentType::JSON)) {
            $this->parameters = $this->parseJson();
        }
        if (str_contains($contentType, ContentType::XML)
            || str_contains($contentType, ContentType::XML_APP)) {
            $this->parameters = $this->parseXml();
        }
    }

    /**
     * Retrieves the custom set HTTP method within the body if available (__method). This is useful because only GET and
     * POST are possible with html forms, so to use other HTTP method we need to use a custom field __method to
     * override the given method (e.g. override POST to PUT).
     *
     * @return HttpMethod|null
     */
    public function getHttpMethodOverride(): ?HttpMethod
    {
        $method = $this->getParameter(self::CUSTOM_METHOD_PARAMETER, "");
        return HttpMethod::tryFrom(strtoupper($method));
    }

    /**
     * Reads the raw data as it was passed to build the instance.
     *
     * @return string
     */
    public function getRawData(): string
    {
        return $this->rawData;
    }

    /**
     * Retrieves the content type used to encode the request data by the client (e.g. application/x-www-form-urlencoded,
     * application/json, etc.).
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Retrieves the entire body parameters (does not include the $_GET variable since its part of the query string
     * and is not considered within the request body).
     *
     * @return array
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Verifies if the given parameter exists in the request body.
     *
     * @param string $name
     * @return bool
     */
    public function hasParameter(string $name): bool
    {
        return isset($this->parameters[$name]);
    }

    /**
     * Retrieves one specific parameter from the body data. If the specified parameter doesn't exist, the method
     * returns the given default value (defaults to null).
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getParameter(string $name, mixed $default = null): mixed
    {
        return $this->parameters[$name] ?? $default;
    }

    /**
     * Manually sets a parameter to the request body which was not previously parsed or needs to be modified.
     *
     * @param string $name
     * @param string $value
     */
    public function setParameter(string $name, mixed $value): void
    {
        $this->parameters[$name] = $value;
    }

    /**
     * Sets a list of parameters into the request body.
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters): void
    {
        foreach ($parameters as $name => $value) {
            $this->setParameter($name, $value);
        }
    }

    /**
     * Parses the raw string (which should have a format like first=value&arr[]=foo+bar&arr[]=baz) into an array of
     * parameters. All parameters are already url decoded.
     *
     * @see https://www.php.net/manual/fr/function.parse-str
     * @return array
     */
    private function parseUrlEncodedForm(): array
    {
        $parameters = [];
        parse_str($this->rawData, $parameters);
        return $parameters;
    }

    /**
     * When you submit a form using multipart/form-data as the content type, the data is sent in a format that is not
     * directly accessible through php://input. In PHP, when you use multipart/form-data, the data is parsed and made
     * available in the $_POST and $_FILES super globals.
     *
     * @return array
     */
    private function parseMultiForm(): array
    {
        return array_merge($_POST, $_FILES);
    }

    /**
     * @throws JsonParseException
     */
    private function parseJson(): array
    {
        $decodedJson = json_decode($this->rawData);
        if (is_null($decodedJson)) {
            throw new JsonParseException($this->rawData);
        }
        return (array) $decodedJson;
    }

    /**
     * @throws XmlParseException
     */
    private function parseXml(): array
    {
        try {
            return $this->xmlElementToStdClass(new SimpleXMLElement($this->rawData));
        } catch (Exception $e) {
            throw new XmlParseException($this->rawData, $e->getMessage());
        }
    }

    private function xmlElementToStdClass(SimpleXMLElement $root): array
    {
        $arrayRoot = (array) $root;
        foreach ($arrayRoot as &$element) {
            if ($element instanceof SimpleXMLElement) {
                $element = (object) $this->xmlElementToStdClass($element);
            }
        }
        return $arrayRoot;
    }
}
