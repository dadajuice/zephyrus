<?php namespace Zephyrus\Network;

use Zephyrus\Exceptions\RouteMethodUnsupportedException;

class ServerEnvironnement
{
    private array $serverVariables;
    private array $headers;

    public function __construct(array $serverVariables)
    {
        $this->serverVariables = $serverVariables;
        $this->headers = array_change_key_case(getallheaders(), CASE_UPPER);
    }

    public function getCookies(): array
    {
        return $_COOKIE;
    }

    public function getRequestedUrl(): string
    {
        $protocol = $this->isHttps() ? "https" : "http";
        $serverPort = $this->getServerPort();
        $defaultPorts = ['http' => 80, 'https' => 443];
        $port = $serverPort != $defaultPorts[$protocol] ? ":$serverPort" : "";
        return "$protocol://" . $this->getHostname() . $port . $this->getRoute();
    }

    public function getRawData(): string
    {
        return file_get_contents("php://input") ?? "";
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtoupper($name)] ?? null;
    }

    public function getRoute(): string
    {
        return $this->serverVariables['REQUEST_URI'] ?? "/";
    }

    public function getContentType(): string
    {
        return $this->serverVariables['CONTENT_TYPE'] ?? ContentType::PLAIN;
    }

    public function getMethod(): HttpMethod // TODO: On load
    {
        $requestedMethod = $this->serverVariables['REQUEST_METHOD'] ?? 'GET';
        $method = HttpMethod::tryFrom($requestedMethod);
        if (is_null($method)) {
            throw new RouteMethodUnsupportedException($requestedMethod);
        }
        return $method;
    }

    public function getClientIp(): string
    {
        return $this->serverVariables['REMOTE_ADDR'] ?? "";
    }

    public function getUserAgent(): string
    {
        return $this->serverVariables['HTTP_USER_AGENT'] ?? "";
    }

    public function getAccept(): string
    {
        return $this->serverVariables['HTTP_ACCEPT'] ?? "*/*";
    }

    public function getServerPort(): string
    {
        return $this->serverVariables['SERVER_PORT'] ?? "80";
    }

    public function getReferer(): string
    {
        return $this->serverVariables['HTTP_REFERER'] ?? "";
    }

    public function getProtocol(): string
    {
        return $this->serverVariables['SERVER_PROTOCOL'] ?? "HTTP/1.0";
    }

    public function isHttps(): bool
    {
        return ($this->serverVariables['HTTPS'] ?? "") == 'on';
    }

    public function getHostname(): string
    {
        return $this->serverVariables['HTTP_HOST'] ?? $this->serverVariables['SERVER_NAME'] ?? "localhost";
    }

    public function getServerVariable(string $name, ?string $defaultValue = null): ?string
    {
        return $this->serverVariables[$name] ?? $defaultValue;
    }

    public function getServerVariables(): array
    {
        return $this->serverVariables;
    }
}
