<?php namespace Zephyrus\Network;

use CURLFile;
use CurlHandle;
use InvalidArgumentException;
use Zephyrus\Exceptions\HttpRequesterException;

class HttpRequester
{
    const DEFAULT_CONNECTION_TIMEOUT = 15;
    const DEFAULT_CONTENT_TYPE = ContentType::FORM;

    private array $headers = [];
    private string $url;
    private string $method;
    private int $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT;
    private bool $followRedirection = true;
    private bool $verifySsl = true;
    private string $userAgent = "Zephyrus HTTP Requester/1.0.0";
    private array $options = [];
    private string $contentType = self::DEFAULT_CONTENT_TYPE;
    private string $response;
    private array $responseResults = [];

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
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $localFilePath);
        finfo_close($info);
        if (is_null($uploadFilename)) {
            $uploadFilename = pathinfo($localFilePath, PATHINFO_FILENAME);
        } else {
            $givenExtension = pathinfo($uploadFilename, PATHINFO_EXTENSION);
            if (empty($givenExtension)) {
                $extension = pathinfo($localFilePath, PATHINFO_EXTENSION);
                if (!empty($extension)) {
                    $uploadFilename .= '.' . $extension;
                }
            }
        }
        return new CurlFile($localFilePath, $mime, $uploadFilename);
    }

    public function __construct(string $method, string $url)
    {
        $this->method = strtolower($method);
        $this->url = $url;
    }

    /**
     * Executes an HTTP request that returns some sort of stream (e.g. SSE). Will execute the given callback with the
     * cumulated results and request info. Does not return anything since the processing of the request is done via the
     * specified callback.
     *
     * @param callable $callback
     * @param string|array $payload
     * @throws HttpRequesterException
     */
    public function executeStream(callable $callback, string|array $payload = "")
    {
        $this->addOption(CURLOPT_WRITEFUNCTION, function ($curl, $data) use ($callback) {
            $bytes = strlen($data);
            static $buf = '';
            $buf .= $data;
            $info = curl_getinfo($curl);

            while (1) {
                $pos = strpos($buf, "\n");
                if ($pos === false) {
                    break;
                }

                // @codeCoverageIgnoreStart
                $data = substr($buf, 0, $pos + 1);
                $buf = substr($buf, $pos + 1);
                if (strlen($data) > 50) {
                    $results = str_replace("data:", "", $data);
                    ($callback)($results, $info);
                }
                // @codeCoverageIgnoreEnd
            }

            return $bytes;
        });
        $this->execute($payload);
    }

    /**
     * Executes the HTTP request to upload the given file. Along with the file, a list of parameters can optionally be
     * sent. Be warned that the content type will be automatically overridden to multipart/form-data.
     *
     * @param CURLFile $file
     * @param string $name
     * @param array $payload
     * @throws HttpRequesterException
     */
    public function executeUpload(CURLFile $file, string $name = 'file', array $payload = [])
    {
        $this->execute(array_merge([$name => $file], $payload));
    }

    /**
     * Executes the HTTP request as a file download. The saved file can be defined with the filePath argument
     * optionally. If none is given, the default temp folder will be used. This method returns the complete filepath of
     * the downloaded file.
     *
     * @param string|array $payload
     * @param string|null $filePath
     * @throws HttpRequesterException
     * @return string
     */
    public function executeDownload(string|array $payload = "", ?string $filePath = null): string
    {
        if (is_null($filePath)) {
            $filePath = tempnam(sys_get_temp_dir(), "zephyrus");
        }
        $file = @fopen($filePath, 'w+');
        if ($file === false) {
            throw new HttpRequesterException("Cannot open file [$filePath] for download", $this->method, $this->url);
        }
        $this->addOption(CURLOPT_TIMEOUT, 50);
        $this->addOption(CURLOPT_FILE, $file);
        $this->execute($payload);
        fclose($file);
        return $filePath;
    }

    /**
     * Executes the HTTP request with the given payload. The payload can either be an array for the form content types
     * e.g. application/x-www-form-urlencoded or a string for other type such as application/json. If the payload is
     * an array and contains a CURLFile, it will automatically be handled as an upload attempt and switch the content
     * type to multipart/form-data.
     *
     * @param string|array $payload
     * @throws HttpRequesterException
     * @return string
     */
    public function execute(string|array $payload = ""): string
    {
        if (is_array($payload) && $this->hasCurlFile($payload)) {
            $this->setContentType(ContentType::FORM_MULTIPART);
            $payload = $this->prepareMultipartFormData($payload);
        }
        $curl = $this->buildCurl($payload);
        $this->response = curl_exec($curl);
        if ($this->response === false) {
            throw new HttpRequesterException(curl_error($curl), $this->method, $this->url);
        }
        $this->responseResults = curl_getinfo($curl);
        curl_close($curl);
        return $this->response;
    }

    /**
     * Adds an HTTP header to the request.
     *
     * @param string $name
     * @param string $value
     */
    public function addHeader(string $name, string $value)
    {
        $this->headers[] = "$name:$value";
    }

    /**
     * Adds a list of HTTP header to the request. Array must be associative where the keys are the header name.
     *
     * @param array $headers
     */
    public function addHeaders(array $headers)
    {
        foreach ($headers as $name => $value) {
            $this->headers[] = "$name:$value";
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
     * Retrieve the raw response obtained after the request execution. Each execute method should return its proper
     * expected response.
     *
     * @return string
     */
    public function getResponse(): string
    {
        return $this->response;
    }

    /**
     * Retrieves the HTTP response code (e.g. 200) after the request execution.
     *
     * @return int
     */
    public function getResponseHttpCode(): int
    {
        return $this->responseResults['http_code'];
    }

    /**
     * Retrieves all response information gather by cURL after the request execution.
     *
     * @see https://www.php.net/manual/fr/function.curl-getinfo.php
     * @return array
     */
    public function getResponseInfo(): array
    {
        return $this->responseResults;
    }

    /**
     * Retrieves the content type used for the HTTP Request.
     *
     * @return string
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Prepares the cURL instance based on the requester configurations.
     *
     * @param string|array $payload
     * @throws HttpRequesterException
     * @return CurlHandle
     */
    private function buildCurl(string|array $payload = ""): CurlHandle
    {
        $curl = curl_init($this->buildRequestedUrl($payload));
        if ($curl === false) {
            throw new HttpRequesterException("Cannot instantiate cURL instance for url [$this->url]", $this->method, $this->url);
        }
        $this->setCurlBasicOptions($curl);
        $this->setCurlUserAgent($curl);
        $this->setCurlOptionalMethod($curl);
        $this->setCurlSsl($curl);
        $this->setCurlAdditionalOptions($curl);
        $this->setCurlHeaders($curl);
        if ($this->method != 'get') {
            $this->setCurlPayload($curl, $payload);
        }
        return $curl;
    }

    /**
     * Adds the url parameters if there is a given payload to the request and the method is GET. Makes sure to properly
     * append to the url the resulting query string.
     *
     * @param string|array $payload
     * @return string
     */
    private function buildRequestedUrl(string|array $payload): string
    {
        $requestedUrl = $this->url;
        if ($this->method == 'get' && is_array($payload) && !empty($payload)) {
            $requestedUrl .= (!str_contains($requestedUrl, '?') ? '?' : '&') . http_build_query($payload);
        }
        return $requestedUrl;
    }

    /**
     * Applies basic cURL options including connection timeout, redirection directive, etc.
     *
     * @param CurlHandle $curl
     */
    private function setCurlBasicOptions(CurlHandle $curl)
    {
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $this->connectionTimeout);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, $this->followRedirection);
    }

    /**
     * Applies the proper request method to the cURL CUSTOMREQUEST option is needed. All method besides get and post
     * are considered "custom" to the cURL instance.
     *
     * @param CurlHandle $curl
     */
    private function setCurlOptionalMethod(CurlHandle $curl)
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
     * Applies the specified content type and any other given headers to the cURL HTTPHEADER option.
     *
     * @param CurlHandle $curl
     */
    private function setCurlHeaders(CurlHandle $curl)
    {
        $this->addHeader('Content-Type', $this->contentType);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
    }

    /**
     * Applies the given payload into the cURL POSTFIELDS option. If the payload registered content type is
     * application/x-www-form-urlencoded, it will proceed to properly encode the query. Otherwise, it will be sent
     * as is.
     *
     * @param CurlHandle $curl
     * @param string|array $payload
     */
    private function setCurlPayload(CurlHandle $curl, string|array $payload)
    {
        curl_setopt($curl, CURLOPT_POST, true);
        if ($this->contentType == ContentType::FORM && is_array($payload)) {
            $payload = http_build_query($payload);
        }
        curl_setopt($curl, CURLOPT_POSTFIELDS, $payload);
    }

    /**
     * Verifies if the payload (only array compatible) has a CURLFile instance within its data. If it happens, it means
     * the request needs to be a form data content type.
     *
     * @param array $payload
     * @return bool
     */
    private function hasCurlFile(array $payload): bool
    {
        foreach ($payload as $data) {
            if (is_array($data) && $this->hasCurlFile($data)) {
                return true;
            }
            if ($data instanceof \CURLFile) {
                return true;
            }
        }
        return false;
    }

    /**
     * Corrects a problem with cURL while sending array in multipart/form-data. Reconstruct an array with the proper
     * formatting needed by multipart content type. Can go up to 2 levels of nested array. Needs to be done recursively
     * to allow an unlimited amount of levels.
     *
     * @param array $payload
     * @return array
     */
    private function prepareMultipartFormData(array $payload): array
    {
        $parameters = [];
        foreach ($payload as $parameterName => $parameterValue) {
            // Problem with cURL while sending array in multipart/form-data
            if (is_array($parameterValue)) {
                foreach ($parameterValue as $key => $value) {
                    if (is_array($value)) {
                        // Nested array case (2 levels)
                        foreach ($value as $innerKey => $innerValue) {
                            $parameters[$parameterName . '[' . $key . '][' . $innerKey . ']'] = $innerValue;
                        }
                    } else {
                        $parameters[$parameterName . '[' . $key . ']'] = $value;
                    }
                }
                continue;
            }
            $parameters[$parameterName] = $parameterValue;
        }
        return $parameters;
    }
}
