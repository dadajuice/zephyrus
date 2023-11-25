<?php namespace Zephyrus\Tests;

use Zephyrus\Application\Configuration;
use Zephyrus\Network\ContentType;
use Zephyrus\Network\HttpMethod;
use Zephyrus\Network\Request;
use Zephyrus\Network\ServerEnvironnement;
use Zephyrus\Security\CsrfGuard;

class RequestUtility
{
    public static function get(string $route, array $configurations = []): Request
    {
        return self::buildFormRequest($route, HttpMethod::GET, "", $configurations);
    }

    public static function post(string $route, string $formBody = "", array $configurations = []): Request
    {
        if (Configuration::getSecurity('csrf')['enabled'] ?? false) {
            $csrf = CsrfGuard::generate();
            $formBody = CsrfGuard::REQUEST_TOKEN_VALUE . "=" . $csrf . (!empty($formBody) ? '&' . $formBody : "");
        }
        return self::buildFormRequest($route, HttpMethod::POST, $formBody, $configurations);
    }

    public static function put(string $route, string $formBody = "", array $configurations = []): Request
    {
        if (Configuration::getSecurity('csrf')['enabled'] ?? false) {
            $csrf = CsrfGuard::generate();
            $formBody = CsrfGuard::REQUEST_TOKEN_VALUE . "=" . $csrf . (!empty($formBody) ? '&' . $formBody : "");
        }
        return self::buildFormRequest($route, HttpMethod::PUT, $formBody, $configurations);
    }

    public static function patch(string $route, string $formBody = "", array $configurations = []): Request
    {
        if (Configuration::getSecurity('csrf')['enabled'] ?? false) {
            $csrf = CsrfGuard::generate();
            $formBody = CsrfGuard::REQUEST_TOKEN_VALUE . "=" . $csrf . (!empty($formBody) ? '&' . $formBody : "");
        }
        return self::buildFormRequest($route, HttpMethod::PATCH, $formBody, $configurations);
    }

    public static function delete(string $route, string $formBody = "", array $configurations = []): Request
    {
        if (Configuration::getSecurity('csrf')['enabled'] ?? false) {
            $csrf = CsrfGuard::generate();
            $formBody = CsrfGuard::REQUEST_TOKEN_VALUE . "=" . $csrf . (!empty($formBody) ? '&' . $formBody : "");
        }
        return self::buildFormRequest($route, HttpMethod::DELETE, $formBody, $configurations);
    }

    public static function getJson(string $route, array $configurations = []): Request
    {
        return self::buildJsonRequest($route, HttpMethod::GET, "", $configurations);
    }

    public static function postJson(string $route, string $jsonBody = "", array $configurations = []): Request
    {
        return self::buildJsonRequest($route, HttpMethod::POST, $jsonBody, $configurations);
    }

    public static function putJson(string $route, string $jsonBody = "", array $configurations = []): Request
    {
        return self::buildJsonRequest($route, HttpMethod::PUT, $jsonBody, $configurations);
    }

    public static function patchJson(string $route, string $jsonBody = "", array $configurations = []): Request
    {
        return self::buildJsonRequest($route, HttpMethod::PATCH, $jsonBody, $configurations);
    }

    public static function deleteJson(string $route, string $jsonBody = "", array $configurations = []): Request
    {
        return self::buildJsonRequest($route, HttpMethod::DELETE, $jsonBody, $configurations);
    }

    public static function getXml(string $route, array $configurations = []): Request
    {
        return self::buildXmlRequest($route, HttpMethod::GET, "", $configurations);
    }

    public static function postXml(string $route, string $xmlBody = "", array $configurations = []): Request
    {
        return self::buildXmlRequest($route, HttpMethod::POST, $xmlBody, $configurations);
    }

    public static function putXml(string $route, string $xmlBody = "", array $configurations = []): Request
    {
        return self::buildXmlRequest($route, HttpMethod::PUT, $xmlBody, $configurations);
    }

    public static function patchXml(string $route, string $xmlBody = "", array $configurations = []): Request
    {
        return self::buildXmlRequest($route, HttpMethod::PATCH, $xmlBody, $configurations);
    }

    public static function deleteXml(string $route, string $xmlBody = "", array $configurations = []): Request
    {
        return self::buildXmlRequest($route, HttpMethod::DELETE, $xmlBody, $configurations);
    }

    public static function buildFormRequest(string $route, HttpMethod $method, string $formBody = "", array $configurations = []): Request
    {
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', $formBody);
        $request = new Request(new ServerEnvironnement(array_merge([
            'REQUEST_URI' => $route,
            'REQUEST_METHOD' => $method->value,
            'CONTENT_TYPE' => ContentType::FORM
        ], $configurations)));
        stream_wrapper_restore("php");
        (new PhpStreamMock())->unlink();
        return $request;
    }

    public static function buildJsonRequest(string $route, HttpMethod $method, string $jsonBody, array $configurations = []): Request
    {
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', $jsonBody);
        $request = new Request(new ServerEnvironnement(array_merge([
            'REQUEST_URI' => $route,
            'REQUEST_METHOD' => $method->value,
            'CONTENT_TYPE' => ContentType::JSON
        ], $configurations)));
        stream_wrapper_restore("php");
        (new PhpStreamMock())->unlink();
        return $request;
    }

    public static function buildXmlRequest(string $route, HttpMethod $method, string $xmlBody, array $configurations = []): Request
    {
        stream_wrapper_unregister("php");
        stream_wrapper_register("php", "\Zephyrus\Tests\PhpStreamMock");
        file_put_contents('php://input', $xmlBody);
        $request = new Request(new ServerEnvironnement(array_merge([
            'REQUEST_URI' => $route,
            'REQUEST_METHOD' => $method->value,
            'CONTENT_TYPE' => ContentType::XML_APP
        ], $configurations)));
        stream_wrapper_restore("php");
        (new PhpStreamMock())->unlink();
        return $request;
    }
}
