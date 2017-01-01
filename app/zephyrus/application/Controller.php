<?php namespace Zephyrus\Application;

use Zephyrus\Network\ContentType;
use Zephyrus\Network\Response;
use Zephyrus\Network\Router;
use Zephyrus\Utilities\Pager;

abstract class Controller
{
    protected function render($page, $args = [], Pager $pager = null)
    {
        $view = new View($page);
        if (!is_null($pager)) {
            $view->setPager($pager);
        }
        echo $view->render($args);
    }

    protected function html($data)
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::HTML);
        echo $data;
        exit;
    }

    protected function json($data)
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::JSON);
        echo json_encode($data);
        exit;
    }

    protected function sse($data, $id = 0, $retry = 1000)
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::SSE);
        Response::sendHeader('Cache-Control', 'no-cache');
        echo "id: $id" . PHP_EOL;
        echo "retry: " . $retry . PHP_EOL;
        echo "data: " . json_encode($data) . PHP_EOL;
        echo PHP_EOL;
        ob_flush();
        flush();
    }

    protected function xml($data, $root = "")
    {
        Response::sendResponseCode();
        Response::sendContentType(ContentType::XML);
        if (is_array($data)) {
            $xml = new \SimpleXMLElement('<' . $root . '/>');
            array_walk_recursive($data, array ($xml, 'addChild'));
            echo $xml->asXML();
            exit;
        }
        if ($data instanceof \SimpleXMLElement) {
            echo $data->asXML();
            exit;
        }
        throw new \RuntimeException("Cannot parse specified data as XML");
    }

    protected static function bind($method)
    {
        return [get_called_class(), $method];
    }
}