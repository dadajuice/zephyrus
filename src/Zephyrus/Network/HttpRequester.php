<?php namespace Zephyrus\Network;

use CURLFile;
use CurlHandle;
use InvalidArgumentException;
use Zephyrus\Exceptions\HttpRequesterException;
use Zephyrus\Network\HttpRequester\HttpPayload;
use Zephyrus\Network\HttpRequester\HttpRequesterResponse;
use Zephyrus\Network\HttpRequester\RequestTypes\HttpDownloadRequest;
use Zephyrus\Network\HttpRequester\RequestTypes\HttpStreamRequest;
use Zephyrus\Network\HttpRequester\RequestTypes\HttpUploadRequest;
use Zephyrus\Utilities\FileSystem\File;

class HttpRequester
{
    private string $url;
    private string $method;
    private bool $verifySsl = true;
    private bool $followRedirection = true;
    private string $userAgent = "Zephyrus HTTP Requester/1.0.0";
    private array $options = [];
    private array $headers = [];
    private string $contentType = ContentType::FORM;
    private int $connectionTimeout = 15;
    private string $accept = ContentType::ANY;
    private $writeCallback;

    use HttpStreamRequest;
    use HttpDownloadRequest;
    use HttpUploadRequest;

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

    public static function patch(string $url): self
    {
        return new self('patch', $url);
    }

    public static function delete(string $url): self
    {
        return new self('delete', $url);
    }

    /**
     * Returns a compatible CURLFile instance for the given local file (complete path) which can then be used with the
     * executeUpload method. If no uploadFilename is given, the original filename will be used (including extension).
     *
     * @param string $localFilePath
     * @param string|null $uploadFilename
     * @return CURLFile
     */
    public static function prepareUploadFile(string $localFilePath, ?string $uploadFilename = null): CURLFile
    {
        if (!is_readable($localFilePath)) {
            throw new InvalidArgumentException("Specified filepath [$localFilePath] is not readable and thus cannot be prepared as a remote request file transfer");
        }
        return (new File($localFilePath))->buildCurlFile($uploadFilename);
    }

    public function __construct(string $method, string $url)
    {
        $this->method = strtolower($method);
        $this->url = $url;
    }

    /**
     * Executes the HTTP request with the given payload. The payload can either be an array for the form content type
     * e.g. application/x-www-form-urlencoded or a string for other type such as application/json. If the payload is
     * an array and contains a CURLFile, it will automatically be handled as an upload attempt and switch the content
     * type to multipart/form-data.
     *
     * @param array|string $payload
     * @throws HttpRequesterException
     * @return HttpRequesterResponse
     */
    public function execute(string|array $payload = ""): HttpRequesterResponse
    {
        $responseHeaders = [];
        $curl = $this->buildCurl(new HttpPayload($this->contentType, $payload));
        curl_setopt($curl, CURLOPT_HEADERFUNCTION, function ($curl, $header) use (&$responseHeaders) {
            $len = strlen($header);
            $header = explode(':', $header, 2);
            if (count($header) < 2) {
                return $len;
            }
            $responseHeaders[strtolower(trim($header[0]))] = trim($header[1]);
            return $len;
        });
        $response = curl_exec($curl);
        if ($response === false) {
            throw new HttpRequesterException(curl_error($curl), $this->method, $this->url);
        }
        $information = curl_getinfo($curl);
        curl_close($curl);
        return new HttpRequesterResponse($response, $information, $responseHeaders);
    }

    /**
     * Adds an HTTP header to the request. If the header already exists, it will update its value.
     *
     * @param string $name
     * @param string $value
     */
    public function addHeader(string $name, string $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Adds a list of HTTP header to the request. Array must be associative where the keys are the header name.
     *
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers[$name] = $value;
        }
    }

    /**
     * Removes the specified name from the headers list. Nothing is made if the header is already not present.
     *
     * @param string $name
     */
    public function removeHeader(string $name)
    {
        if (isset($this->headers[$name])) {
            unset($this->headers[$name]);
        }
    }

    /**
     * Removes a list of specified names from the headers list. Nothing is made if the header is already not present.
     *
     * @param array $names
     */
    public function removeHeaders(array $names)
    {
        foreach ($names as $name) {
            $this->removeHeader($name);
        }
    }

    /**
     * Adds a cURL custom option to the request.
     *
     * @param string $curlOption
     * @param mixed $value
     */
    public function addOption(string $curlOption, mixed $value)
    {
        $this->options[$curlOption] = $value;
    }

    /**
     * Adds a list of cURL custom options to the request. Array must be associative where the keys are the option name.
     *
     * @param array $curlOptions
     */
    public function addOptions(array $curlOptions)
    {
        foreach ($curlOptions as $curlOption => $value) {
            $this->options[$curlOption] = $value;
        }
    }

    /**
     * Removes the specified cURL option.
     *
     * @param string $curlOption
     */
    public function removeOption(string $curlOption)
    {
        if (isset($this->options[$curlOption])) {
            unset($this->options[$curlOption]);
        }
    }

    /**
     * Removes a list of specified cURL options.
     *
     * @param array $curlOptions
     */
    public function removeOptions(array $curlOptions)
    {
        foreach ($curlOptions as $option) {
            $this->removeOption($option);
        }
    }

    /**
     * Defines the connection timeout in seconds. Defaults to 15 seconds.
     *
     * @param int $connectionTimeout
     */
    public function setConnectionTimeout(int $connectionTimeout)
    {
        $this->connectionTimeout = $connectionTimeout;
    }

    /**
     * Defines if the requester should follow redirections the destination server returns. Defaults to true.
     *
     * @param bool $followRedirection
     */
    public function setFollowRedirection(bool $followRedirection)
    {
        $this->followRedirection = $followRedirection;
    }

    /**
     * Disable or enable the SSL verification. Should always be enabled in production environnement. Disabling is
     * useful when using a self-signed certificate. Handles the CURLOPT_SSL_VERIFYPEER and CURLOPT_SSL_VERIFYHOST
     * options.
     *
     * @param bool $verifySsl
     * @return void
     */
    public function setSslVerification(bool $verifySsl)
    {
        $this->verifySsl = $verifySsl;
    }

    /**
     * Defines the requester user agent to use for the request.
     *
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent)
    {
        $this->userAgent = $userAgent;
    }

    public function setWriteCallback(callable $callback)
    {
        $this->writeCallback = $callback;
    }

    /**
     * Defines the content type used to send the request. Defaults to application/x-www-form-urlencoded.
     *
     * @param string $contentType
     */
    public function setContentType(string $contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * Prepares the cURL instance based on the requester configurations.
     *
     * @param HttpPayload $payload
     * @return CurlHandle
     */
    private function buildCurl(HttpPayload $payload): CurlHandle
    {
        $this->addHeader('Content-Type', $payload->getContentType());
        $curl = curl_init();
        $this->setDefaultHeaders();
        $this->setCurlBasicOptions($curl);
        $this->setCurlUserAgent($curl);
        $this->setCurlCustomMethod($curl);
        $this->setCurlSsl($curl);
        $this->setCurlWriteFunction($curl);
        $this->setCurlAdditionalOptions($curl);
        $this->setCurlHeaders($curl);
        $this->setCurlPayload($curl, $payload);
        return $curl;
    }

    /**
     * Applies the specified Accept and any other default headers.
     */
    private function setDefaultHeaders()
    {
        if (!empty($this->accept)) {
            $this->addHeader('Accept', $this->accept);
        }
    }

    /**
     * Applies basic cURL options including connection timeout, redirection directive, etc.
     *
     * @param CurlHandle $curl
     */
    private function setCurlBasicOptions(CurlHandle $curl)
    {
        curl_setopt($curl, CURLOPT_URL, $this->url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->followRedirection);
        curl_setopt($curl, CURLINFO_HEADER_OUT, true);
    }

    /**
     * Applies the proper request method to the cURL CUSTOMREQUEST option is needed. All method besides get and post
     * are considered "custom" to the cURL instance.
     *
     * @param CurlHandle $curl
     */
    private function setCurlCustomMethod(CurlHandle $curl)
    {
        if ($this->method != 'get' && $this->method != 'post') {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, strtoupper($this->method));
        }
    }

    /**
     * Applies the user agent option to the cURL instance.
     *
     * @param CurlHandle $curl
     */
    private function setCurlUserAgent(CurlHandle $curl)
    {
        if (!empty($this->userAgent)) {
            curl_setopt($curl, CURLOPT_USERAGENT, $this->userAgent);
        }
    }

    private function setCurlWriteFunction(CurlHandle $curl)
    {
        if (!is_null($this->writeCallback)) {
            curl_setopt($curl, CURLOPT_WRITEFUNCTION, $this->writeCallback);
        }
    }

    /**
     * Applies the SSL verification options to the cURL instance.
     *
     * @param CurlHandle $curl
     */
    private function setCurlSsl(CurlHandle $curl)
    {
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, $this->verifySsl ? 2 : 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, $this->verifySsl ? 2 : 0);
    }

    /**
     * Applies any other specified cURL options.
     *
     * @param CurlHandle $curl
     */
    private function setCurlAdditionalOptions(CurlHandle $curl)
    {
        foreach ($this->options as $curlOption => $value) {
            curl_setopt($curl, $curlOption, $value);
        }
    }

    /**
     * Applies the specified Content-Type, Accept and any other given headers to the cURL HTTPHEADER option using the
     * proper required formatting by cURL instance.
     *
     * @param CurlHandle $curl
     */
    private function setCurlHeaders(CurlHandle $curl)
    {
        $curlCompatibleHeaders = [];
        foreach ($this->headers as $name => $value) {
            $curlCompatibleHeaders[] = "$name:$value";
        }
        curl_setopt($curl, CURLOPT_HTTPHEADER, $curlCompatibleHeaders);
    }

    /**
     * Applies the given payload into the cURL POSTFIELDS option. If the payload registered content type is
     * application/x-www-form-urlencoded, it will proceed to properly encode the query. Otherwise, it will be sent
     * as is.
     *
     * @param CurlHandle $curl
     * @param HttpPayload $payload
     */
    private function setCurlPayload(CurlHandle $curl, HttpPayload $payload)
    {
        if (empty($payload->getContent())) {
            return;
        }
        if ($this->method == 'get') {
            $requestedUrl = $this->url . (!str_contains($this->url, '?') ? '?' : '&') . $payload->getContent();
            curl_setopt($curl, CURLOPT_URL, $requestedUrl);
            return;
        }
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload->getContent());
    }
}
