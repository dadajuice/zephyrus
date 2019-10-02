<?php namespace Zephyrus\Network;

use CURLFile;
use InvalidArgumentException;
use Zephyrus\Exceptions\HttpRequestException;

class HttpRequest
{
    const DEFAULT_CONNECTION_TIMEOUT = 15;

    /**
     * @var array
     */
    private $headers = [];

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $method;

    /**
     * @var array
     */
    private $parameters = [];

    /**
     * @var int
     */
    private $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT;

    /**
     * @var bool
     */
    private $followRedirection = true;

    /**
     * @var string
     */
    private $userAgent = "";

    /**
     * @var array
     */
    private $options = [];

    /**
     * @var string
     */
    private $response;

    /**
     * @var array
     */
    private $responseResults = [];

    public static function get(string $url): self
    {
        return new self('get', $url);
    }

    public static function post(string $url): self
    {
        return new self('post', $url);
    }

    public static function put(string $url): self
    {
        return new self('put', $url);
    }

    public static function delete(string $url): self
    {
        return new self('delete', $url);
    }

    public function __construct(string $method, string $url)
    {
        $this->method = strtolower($method);
        $this->url = $url;
    }

    public function execute(array $parameters = [])
    {
        $curl = $this->buildCurl(array_merge($this->parameters, $parameters));
        if (!$this->response = curl_exec($curl)) {
            throw new HttpRequestException(curl_error($curl), $this->method, $this->url);
        }
        $this->responseResults = curl_getinfo($curl);
        curl_close($curl);
        return $this->response;
    }

    public function addParameter(string $name, $value)
    {
        $this->parameters[$name] = $value;
    }

    /**
     * @param string $name
     * @param string $filepath
     * @param string $uploadFilename
     * @throws InvalidArgumentException
     */
    public function addFileParameter(string $name, string $filepath, string $uploadFilename)
    {
        $this->parameters[$name] = $this->buildUploadFile($filepath, $uploadFilename);
    }

    public function addParameters(array $parameters)
    {
        foreach ($parameters as $name => $value) {
            $this->parameters[$name] = $value;
        }
    }

    public function addHeader(string $name, string $value)
    {
        $this->headers[] = "$name:$value";
    }

    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers[] = "$name:$value";
        }
    }

    public function addOption(string $curlOption, string $value)
    {
        $this->options[$curlOption] = $value;
    }

    public function addOptions(array $curlOptions)
    {
        foreach ($curlOptions as $curlOption => $value) {
            $this->options[$curlOption] = $value;
        }
    }

    /**
     * @param int $connectionTimeout
     */
    public function setConnectionTimeout(int $connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * @param bool $followRedirection
     */
    public function setFollowRedirection(bool $followRedirection)
    {
        $this->followRedirection = $followRedirection;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;
    }

    public function getResponse()
    {
        return $this->response;
    }

    public function getResponseHttpCode()
    {
        return $this->responseResults['http_code'];
    }

    public function getResponseInfo(): array
    {
        return $this->responseResults;
    }

    private function buildCurl(array $data = [])
    {
        $curl = curl_init($this->buildRequestedUrl($data));
        $this->setCurlBasicOptions($curl);
        $this->setCurlUserAgent($curl);
        $this->setCurlOptionalMethod($curl);
        $this->setCurlAdditionalOptions($curl);
        $this->setCurlData($curl, $data);
        return $curl;
    }

    private function buildRequestedUrl(array $data = []): string
    {
        $requestedUrl = $this->url;
        if ($this->method == 'get' && !empty($data)) {
            $requestedUrl .= ((strpos($requestedUrl, '?') === false) ? '?' : '&')
                . http_build_query($data);
        }
        return $requestedUrl;
    }

    private function setCurlBasicOptions(&$curl)
    {
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->followRedirection);
    }

    private function setCurlOptionalMethod(&$curl)
    {
        if ($this->method != 'get' && $this->method != 'post') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
        }
    }

    private function setCurlUserAgent(&$curl)
    {
        if (!empty($this->userAgent)) {
            curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        }
    }

    private function setCurlAdditionalOptions(&$curl)
    {
        foreach ($this->options as $curlOption => $value) {
            curl_setopt($curl, $curlOption, $value);
        }
    }

    private function setCurlData(&$curl, array $data)
    {
        if ($this->method != 'get') {
            curl_setopt($curl,CURLOPT_POST, count($data));
            $hasUpload = false;
            foreach ($data as $name => $value) {
                if ($value instanceof CURLFile) {
                    $hasUpload = true;
                }
            }
            curl_setopt($curl,CURLOPT_POSTFIELDS, ($hasUpload) ? $data : http_build_query($data));
        }
    }

    /**
     * @param string $filepath
     * @param string $uploadFilename
     * @throws InvalidArgumentException
     * @return CURLFile
     */
    private function buildUploadFile(string $filepath, string $uploadFilename): CURLFile
    {
        if (!is_readable($filepath)) {
            throw new InvalidArgumentException("Specified filepath [$filepath] is not readable and thus cannot be prepared as a remote request file transfer");
        }
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $filepath);
        finfo_close($info);
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        return new CurlFile($filepath, $mime,$uploadFilename . '.' . $extension);
    }
}
