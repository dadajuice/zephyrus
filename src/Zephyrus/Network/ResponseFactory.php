<?php namespace Zephyrus\Network;

use Zephyrus\Application\Form;
use Zephyrus\Application\ViewBuilder;

class ResponseFactory
{
    /**
     * @var ResponseFactory
     */
    private static $instance = null;

    public static function getInstance(): ResponseFactory
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function buildView($page, $args = []): Response
    {
        $response = $this->tryToBuildPhpView($page, $args);
        if (!is_null($response)) {
            return $response;
        }
        $response = new Response(ContentType::HTML);
        $view = ViewBuilder::getInstance()->build($page);
        $response->setContent($view->render($args));
        return $response;
    }

    public function buildHtml(string $data): Response
    {
        $response = new Response(ContentType::HTML);
        $response->setContent($data);
        return $response;
    }

    public function buildJson($data): Response
    {
        $response = new Response(ContentType::JSON);
        $response->setContent(json_encode($data));
        return $response;
    }

    public function buildSse($data, $eventId = 0, $retry = 1000): Response
    {
        $response = new Response(ContentType::SSE);
        $response->addHeader('Cache-Control', 'no-cache');
        ob_start();
        echo "id: $eventId" . PHP_EOL;
        echo "retry: " . $retry . PHP_EOL;
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        $response->setContent(ob_get_clean());
        flush();
        return $response;
    }

    public function buildRedirect(string $url): Response
    {
        $response = new Response(ContentType::PLAIN, 303);
        $response->addHeader('Location', $url);
        flush();
        return $response;
    }

    public function buildXml($data, $root = ""): Response
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

    private function tryToBuildPhpView($page, $args = []): ?Response
    {
        $response = new Response(ContentType::HTML);
        $path = ROOT_DIR . '/app/Views/' . $page . '.php';
        if (file_exists($path) && is_readable($path)) {
            ob_start();
            foreach ($args as $name => $value) {
                $$name = $value;
            }
            include $path;
            $response->setContent(ob_get_clean());
            Form::removeMemorizedValue();
            return $response;
        }
        return null;
    }
}
