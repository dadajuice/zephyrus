<?php namespace Zephyrus\Network;

use CURLFile;
use InvalidArgumentException;
use Zephyrus\Exceptions\HttpRequesterException;

class HttpRequester
{
    const DEFAULT_CONNECTION_TIMEOUT = 15;
    const DEFAULT_CONTENT_TYPE = 'application/x-www-form-urlencoded';

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
    private $contentType = self::DEFAULT_CONTENT_TYPE;

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

    public static function patch(string $url): self
    {
        return new self('patch', $url);
    }

    public static function delete(string $url): self
    {
        return new self('delete', $url);
    }

    /**
     * @param string $filepath
     * @param string $uploadFilename
     * @throws InvalidArgumentException
     * @return CURLFile
     */
    public static function prepareUploadFile(string $filepath, string $uploadFilename): CURLFile
    {
        if (!is_readable($filepath)) {
            throw new InvalidArgumentException("Specified filepath [$filepath] is not readable and thus cannot be prepared as a remote request file transfer");
        }
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($info, $filepath);
        finfo_close($info);
        $extension = pathinfo($filepath, PATHINFO_EXTENSION);
        return new CurlFile($filepath, $mime, $uploadFilename . '.' . $extension);
    }

    public function __construct(string $method, string $url)
    {
        $this->method = strtolower($method);
        $this->url = $url;
    }

    public function executeStream($callback, array $parameters = [])
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

                // Trim buffer
                $data = substr($buf, 0, $pos + 1);
                $buf = substr($buf, $pos + 1);

                // Only log if there is something there
                if (strlen($data) > 50) {
                    // Removes "data:" prefix of SSE
                    $results = str_replace("data:", "", $data);
                    ($callback)($results, $info);
                }
            }

            return $bytes;
        });
        return $this->execute($parameters);
    }

    public function executeDownload(array $parameters = [], ?string $filePath = null): string
    {
        if (is_null($filePath)) {
            $filePath = tempnam(sys_get_temp_dir(), "zeph");
        }
        $file = fopen($filePath, 'w+');
        if ($file === false) {
            throw new HttpRequesterException("Cannot open file [$filePath] for download", $this->method, $this->url);
        }
        $this->addOption(CURLOPT_TIMEOUT, 50);
        $this->addOption(CURLOPT_FILE, $file);
        $this->execute($parameters);
        fclose($file);
        return $filePath;
    }

    public function execute($parameters = []): string
    {
        $curl = $this->buildCurl($parameters);
        $this->response = curl_exec($curl);
        if ($this->response === false) {
            throw new HttpRequesterException(curl_error($curl), $this->method, $this->url);
        }
        $this->responseResults = curl_getinfo($curl);
        curl_close($curl);
        return $this->response;
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

    public function addOption(string $curlOption, $value)
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

    /**
     * @param string $contentType
     */
    public function setContentType(string $contentType)
    {
        $this->contentType = $contentType;
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

    private function buildCurl($data = [])
    {
        $curl = curl_init($this->buildRequestedUrl($data));
        $this->setCurlBasicOptions($curl);
        $this->setCurlUserAgent($curl);
        $this->setCurlOptionalMethod($curl);
        $this->setCurlAdditionalOptions($curl);
        $this->setCurlData($curl, $data);
        return $curl;
    }

    private function buildRequestedUrl($data = []): string
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

    private function setCurlData(&$curl, $data)
    {
        $parameters = $data;
        $hasUpload = $this->hasUploadedFile($data);
        $this->addHeader('Content-Type', ($hasUpload) ? 'multipart/form-data' : $this->contentType);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->headers);
        if ($hasUpload) {
            $parameters = $this->prepareMultipartFormData($data);
        }
        if ($this->method != 'get') {
            curl_setopt($curl, CURLOPT_POST, count($parameters));
            curl_setopt($curl, CURLOPT_POSTFIELDS, ($hasUpload || !is_array($parameters))
                ? $parameters
                : http_build_query($parameters));
        }
    }

    private function hasUploadedFile($data)
    {
        if (is_array($data)) {
            foreach ($data as $value) {
                if ($value instanceof CURLFile) {
                    return true;
                }
            }
        }
        return false;
    }

    private function prepareMultipartFormData(array $data)
    {
        $parameters = [];
        foreach ($data as $parameterName => $parameterValue) {
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
